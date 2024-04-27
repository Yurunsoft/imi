<?php

declare(strict_types=1);

namespace Imi\Model\SoftDelete\Traits;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\BeanFactory;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Db\Query\Interfaces\IResult;
use Imi\Db\Query\Interfaces\IWhereCollector;
use Imi\Event\Event;
use Imi\Model\Contract\IModelQuery;
use Imi\Model\Event\ModelEvents;
use Imi\Model\Event\Param\BeforeDeleteEventParam;
use Imi\Model\Model;
use Imi\Model\ModelRelationManager;
use Imi\Model\SoftDelete\Annotation\SoftDelete;

trait TSoftDelete
{
    /**
     * 生成软删除字段的值
     *
     * @return mixed
     */
    public function __generateSoftDeleteValue()
    {
        return time();
    }

    /**
     * @param string|object $object
     */
    public static function __getSoftDeleteAnnotation($object = null): SoftDelete
    {
        if ($object)
        {
            $class = BeanFactory::getObjectClass($object);
        }
        else
        {
            $class = static::__getRealClassName();
        }

        /** @var SoftDelete|null $softDeleteAnnotation */
        $softDeleteAnnotation = AnnotationManager::getClassAnnotations($class, SoftDelete::class, true, true);
        if (!$softDeleteAnnotation)
        {
            throw new \RuntimeException(sprintf('@SoftDelete Annotation not found in class %s', $class));
        }

        return $softDeleteAnnotation;
    }

    /**
     * 返回一个查询器.
     *
     * @param string|null $poolName  连接池名，为null则取默认
     * @param int|null    $queryType 查询类型；Imi\Db\Query\QueryType::READ/WRITE
     */
    public static function query(?string $poolName = null, ?int $queryType = null, ?string $queryClass = null, ?string $alias = null): IModelQuery
    {
        /** @var IModelQuery $query */
        $query = parent::query($poolName, $queryType, $queryClass, $alias);
        $softDeleteAnnotation = self::__getSoftDeleteAnnotation();
        $whereMethod = $softDeleteAnnotation->postWhere ? 'postWhere' : 'whereBrackets';

        return $query->{$whereMethod}(static function (IQuery $query, IWhereCollector $whereCollector) use ($softDeleteAnnotation) {
            $table = $query->getOption()->table;
            if (null === ($alias = $table->getAlias()))
            {
                $tableName = $table->getTable();
                if ('' !== ($prefix = $table->getPrefix()))
                {
                    $tableName = $prefix . $tableName;
                }
                if (null === ($database = $table->getDatabase()))
                {
                    $fieldTableName = $tableName;
                }
                else
                {
                    $fieldTableName = $database . '.' . $tableName;
                }
            }
            else
            {
                $fieldTableName = $alias;
            }
            $whereCollector->where($fieldTableName . '.' . $softDeleteAnnotation->field, null === $softDeleteAnnotation->default ? 'is' : '=', $softDeleteAnnotation->default);
        });
    }

    /**
     * 返回原始查询器.
     *
     * @param string|null $poolName  连接池名，为null则取默认
     * @param int|null    $queryType 查询类型；Imi\Db\Query\QueryType::READ/WRITE
     */
    public static function originQuery(?string $poolName = null, ?int $queryType = null, ?string $queryClass = null, ?string $alias = null): IModelQuery
    {
        return parent::query($poolName, $queryType, $queryClass, $alias);
    }

    /**
     * 删除记录.
     */
    public function delete(): IResult
    {
        $softDeleteAnnotation = self::__getSoftDeleteAnnotation();
        /** @var IQuery $query */
        $query = static::dbQuery();
        $meta = $this->__meta;
        $isBean = $meta->isBean();

        if ($isBean)
        {
            // 删除前
            $this->trigger(ModelEvents::BEFORE_DELETE, [
                'model' => $this,
                'query' => $query,
            ], $this, \Imi\Model\Event\Param\BeforeDeleteEventParam::class);
        }

        $id = static::PRIMARY_KEYS ?? $meta->getId();
        if ($id)
        {
            foreach ($id as $idName)
            {
                $query->where($idName, '=', $this[$idName]);
            }
        }
        $fieldName = $softDeleteAnnotation->field;
        $fieldVlaue = $this[$fieldName] = $this->__generateSoftDeleteValue();
        $result = $query->update([
            $fieldName => $fieldVlaue,
        ]);

        if ($isBean)
        {
            // 删除后
            $this->trigger(ModelEvents::AFTER_DELETE, [
                'model'  => $this,
                'result' => $result,
            ], $this, \Imi\Model\Event\Param\AfterDeleteEventParam::class);
        }

        if ($meta->hasRelation())
        {
            // 子模型删除
            ModelRelationManager::deleteModel($this);
        }

        $this->__recordExists = false;

        return $result;
    }

    /**
     * 物理删除当前记录.
     */
    public function hardDelete(): IResult
    {
        $this->one(ModelEvents::BEFORE_DELETE, static function (BeforeDeleteEventParam $e) {
            $e->query->getOption()->where = [];
        });

        return parent::delete();
    }

    /**
     * 查找一条被删除的记录.
     *
     * @param callable|mixed ...$ids
     */
    public static function findDeleted(...$ids): ?Model
    {
        if (!$ids)
        {
            return null;
        }
        $softDeleteAnnotation = self::__getSoftDeleteAnnotation();
        $realClassName = static::__getRealClassName();
        $query = static::originQuery()->limit(1);
        if (\is_callable($ids[0]))
        {
            // 回调传入条件
            ($ids[0])($query);
        }
        else
        {
            // 传主键值
            if (\is_array($ids[0]))
            {
                // 键值数组where条件
                foreach ($ids[0] as $name => $value)
                {
                    $query->where($name, '=', $value);
                }
            }
            else
            {
                // 主键值
                foreach (static::PRIMARY_KEYS ?? static::__getMeta()->getId() as $i => $name)
                {
                    if (!isset($ids[$i]))
                    {
                        break;
                    }
                    $query->where($name, '=', $ids[$i]);
                }
            }
            if ($softDeleteAnnotation->postWhere)
            {
                $query->postWhere(static fn (IQuery $query, IWhereCollector $whereCollector) => $whereCollector->where($softDeleteAnnotation->field, '!=', $softDeleteAnnotation->default));
            }
            else
            {
                $query->where($softDeleteAnnotation->field, '!=', $softDeleteAnnotation->default);
            }
        }

        // 查找前
        Event::trigger($realClassName . ':' . ModelEvents::BEFORE_FIND, [
            'ids'   => $ids,
            'query' => $query,
        ], null, \Imi\Model\Event\Param\BeforeFindEventParam::class);

        $result = $query->select()->get();

        // 查找后
        Event::trigger($realClassName . ':' . ModelEvents::AFTER_FIND, [
            'ids'   => $ids,
            'model' => &$result,
        ], null, \Imi\Model\Event\Param\AfterFindEventParam::class);

        return $result;
    }

    /**
     * 恢复当前记录.
     */
    public function restore(): IResult
    {
        $softDeleteAnnotation = self::__getSoftDeleteAnnotation();
        /** @var IQuery $query */
        $query = static::dbQuery();
        $meta = $this->__meta;
        $id = static::PRIMARY_KEYS ?? $meta->getId();
        if ($id)
        {
            foreach ($id as $idName)
            {
                $query->where($idName, '=', $this[$idName]);
            }
        }
        $fieldName = $softDeleteAnnotation->field;
        $result = $query->update([
            $fieldName => ($this[$fieldName] = $softDeleteAnnotation->default),
        ]);
        $this->__recordExists = true;

        return $result;
    }
}
