<?php

declare(strict_types=1);

namespace Imi\Model;

use Imi\App;
use Imi\Bean\IBean;
use Imi\Db\Db;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Db\Query\Interfaces\IResult;
use Imi\Db\Query\QueryType;
use Imi\Db\Query\Raw;
use Imi\Db\Query\Result;
use Imi\Event\Event;
use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\CreateTime;
use Imi\Model\Annotation\UpdateTime;
use Imi\Model\Contract\IModelQuery;
use Imi\Model\Event\ModelEvents;
use Imi\Model\Event\Param\AfterDeleteEventParam;
use Imi\Model\Event\Param\AfterInsertEventParam;
use Imi\Model\Event\Param\AfterSaveEventParam;
use Imi\Model\Event\Param\AfterUpdateEventParam;
use Imi\Model\Event\Param\BeforeDeleteEventParam;
use Imi\Model\Event\Param\BeforeInsertEventParam;
use Imi\Model\Event\Param\BeforeSaveEventParam;
use Imi\Model\Event\Param\BeforeUpdateEventParam;
use Imi\Model\Event\Param\InitEventParam;
use Imi\Model\Traits\TJsonValue;
use Imi\Model\Traits\TListValue;
use Imi\Model\Traits\TSetValue;
use Imi\Util\Imi;
use Imi\Util\LazyArrayObject;
use Imi\Util\ObjectArrayHelper;

/**
 * 常用的数据库模型.
 */
abstract class Model extends BaseModel
{
    use TJsonValue;
    use TListValue;
    use TSetValue;

    public const DEFAULT_QUERY_CLASS = ModelQuery::class;

    /**
     * 第一个主键名称.
     *
     * @var string|null
     */
    public const PRIMARY_KEY = null;

    /**
     * 主键名称数组.
     *
     * @var string[]|null
     */
    public const PRIMARY_KEYS = null;

    /**
     * 动态模型集合.
     */
    protected static array $__forks = [];

    protected static array $__fieldInitParsers = [
        'json' => 'parseJsonInitValue',
        'list' => 'parseListInitValue',
        'set'  => 'parseSetInitValue',
    ];

    protected static array $__fieldSaveParsers = [
        'json' => 'parseJsonSaveValue',
        'list' => 'parseListSaveValue',
        'set'  => 'parseSetSaveValue',
    ];

    protected static array $_fieldParseNullTypes = [
        'json',
    ];

    /**
     * 设置给字段的 SQL 值集合.
     */
    protected array $__rawValues = [];

    public function __construct(array $data = [], bool $queryRelation = true)
    {
        $this->__meta = $meta = static::__getMeta();
        $this->__fieldNames = $meta->getSerializableFieldNames();
        $this->__parsedSerializedFields = $meta->getParsedSerializableFieldNames();
        if (!$this instanceof IBean)
        {
            $this->__init($data, $queryRelation);
        }
    }

    public function __init(array $data = [], bool $queryRelation = true): void
    {
        $meta = $this->__meta;
        $isBean = $meta->isBean();
        if ($isBean)
        {
            // 初始化前
            $this->dispatch(new InitEventParam(ModelEvents::BEFORE_INIT, $this, $data));
        }

        if ($data)
        {
            $this->__originData = $data;
            $fieldAnnotations = $meta->getFields();
            $dbFieldAnnotations = $meta->getDbFields();
            foreach ($data as $k => $v)
            {
                if (isset($fieldAnnotations[$k]))
                {
                    $fieldAnnotation = $fieldAnnotations[$k];
                }
                elseif (isset($dbFieldAnnotations[$k]))
                {
                    $item = $dbFieldAnnotations[$k];
                    $fieldAnnotation = $item['column'];
                    $k = $item['propertyName'];
                }
                else
                {
                    $fieldAnnotation = null;
                }
                /** @var Column|null $fieldAnnotation */
                if ($fieldAnnotation && isset(static::$__fieldInitParsers[$columnType = $fieldAnnotation->type]) && \is_string($v))
                {
                    $v = self::{static::$__fieldInitParsers[$columnType]}($k, $v, $fieldAnnotation, $meta);
                }
                $this[$k] = $v;
            }
        }

        if ($queryRelation && $meta->hasRelation())
        {
            ModelRelationManager::initModel($this);
        }

        if ($isBean)
        {
            // 初始化后
            $this->dispatch(new InitEventParam(ModelEvents::AFTER_INIT, $this, $data));
        }
    }

    /**
     * 返回一个查询器.
     *
     * @param string|null $poolName  连接池名，为null则取默认
     * @param int|null    $queryType 查询类型；Imi\Db\Query\QueryType::READ/WRITE
     */
    public static function query(?string $poolName = null, ?int $queryType = null, ?string $queryClass = null, ?string $alias = null): IModelQuery
    {
        $meta = static::__getMeta(static::__getRealClassName());

        /** @var IModelQuery $query */
        $query = App::newInstance($queryClass ?? static::DEFAULT_QUERY_CLASS, null, $meta->getClassName(), $poolName ?? $meta->getDbPoolName(), $queryType);

        if ($alias)
        {
            $query->getOption()->table->setAlias($alias);
        }

        return $query;
    }

    /**
     * 返回一个数据库查询器，查询结果为数组，而不是当前类实例对象
     *
     * @param string|null $poolName  连接池名，为null则取默认
     * @param int|null    $queryType 查询类型；Imi\Db\Query\QueryType::READ/WRITE
     */
    public static function dbQuery(?string $poolName = null, ?int $queryType = null, ?string $alias = null): IQuery
    {
        $meta = static::__getMeta(static::__getRealClassName());

        $query = Db::query($poolName ?? $meta->getDbPoolName(), null, $queryType)->table($meta->getTableName(), null, $meta->getDatabaseName());

        if ($alias)
        {
            $query->getOption()->table->setAlias($alias);
        }

        return $query;
    }

    /**
     * 判断记录是否存在.
     *
     * @param callable|mixed ...$ids
     */
    public static function exists(...$ids): bool
    {
        if (!$ids)
        {
            throw new \InvalidArgumentException('Model::exists() must pass in parameters');
        }
        $query = static::dbQuery()->limit(1);
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
        }

        return (bool) Db::select('select exists(' . $query->buildSelectSql() . ')', $query->getBinds(), static::__getMeta(static::__getRealClassName())->getDbPoolName(), QueryType::READ)->getScalar();
    }

    /**
     * 查找一条记录.
     *
     * @param callable|mixed ...$ids
     *
     * @return static|null
     */
    public static function find(...$ids): ?self
    {
        if (!$ids)
        {
            return null;
        }
        $query = static::query()->limit(1);
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
        }

        $realClassName = static::__getRealClassName();
        // 查找前
        $event = new \Imi\Model\Event\Param\BeforeFindEventParam($realClassName . '.' . ModelEvents::BEFORE_FIND, $ids, $query);
        Event::dispatch($event);

        $result = $event->query->select()->get();

        // 查找后
        $event = new \Imi\Model\Event\Param\AfterFindEventParam($realClassName . '.' . ModelEvents::AFTER_FIND, $ids, $result);
        Event::dispatch($event);

        return $event->model;
    }

    /**
     * 查询多条记录.
     *
     * @return static[]
     */
    public static function select(array|callable|null $where = null): array
    {
        $realClassName = static::__getRealClassName();
        $query = static::query();
        if ($where)
        {
            self::parseWhere($query, $where);
        }

        // 查询前
        $event = new \Imi\Model\Event\Param\BeforeSelectEventParam($realClassName . '.' . ModelEvents::BEFORE_SELECT, $query);
        Event::dispatch($event);

        $result = $event->query->select()->getArray();

        // 查询后
        $event = new \Imi\Model\Event\Param\AfterSelectEventParam($realClassName . '.' . ModelEvents::AFTER_FIND, $result);
        Event::dispatch($event);

        return $event->result;
    }

    /**
     * 插入记录.
     */
    public function insert(): IResult
    {
        return $this->__insert(self::parseSaveData(iterator_to_array($this), 'insert', $this));
    }

    protected function __insert(mixed $data): IResult
    {
        $query = static::query();
        $meta = $this->__meta;
        $isBean = $meta->isBean();
        if ($isBean)
        {
            // 插入前
            $this->dispatch(new BeforeInsertEventParam($this, $data, $query));
        }

        $result = $query->insert($data);
        if ($result->isSuccess() && ($autoIncrementField = $meta->getAutoIncrementField()))
        {
            $this[$autoIncrementField] = $result->getLastInsertId();
        }

        if ($isBean)
        {
            // 插入后
            $this->dispatch(new AfterInsertEventParam($this, $data, $result));
        }

        if ($meta->hasRelation())
        {
            // 子模型插入
            ModelRelationManager::insertModel($this);
        }
        $this->__originData = array_merge($this->__originData, ObjectArrayHelper::toArray($data));
        $this->__recordExists = true;

        return $result;
    }

    /**
     * 更新记录.
     */
    public function update(): IResult
    {
        return $this->__update(self::parseSaveData(iterator_to_array($this), 'update', $this));
    }

    protected function __update(mixed $data): IResult
    {
        if (!$data || ($data instanceof \Countable && 0 === $data->count()))
        {
            return new Result(true, null, true);
        }
        $query = static::query()->limit(1);
        $meta = $this->__meta;
        $isBean = $meta->isBean();

        if ($isBean)
        {
            // 更新前
            $this->dispatch(new BeforeUpdateEventParam($this, $data, $query));
        }

        $hasIdWhere = false;
        foreach (static::PRIMARY_KEYS ?? $meta->getId() as $idName)
        {
            if (isset($data[$idName]))
            {
                $query->where($idName, '=', $data[$idName]);
                $hasIdWhere = true;
            }
            elseif (isset($this[$idName]))
            {
                $query->where($idName, '=', $this[$idName]);
                $hasIdWhere = true;
            }
        }
        if (!$hasIdWhere)
        {
            throw new \RuntimeException('Use Model->update(), primary key can not be null');
        }

        $result = $query->update($data);

        if ($isBean)
        {
            // 更新后
            $this->dispatch(new AfterUpdateEventParam($this, $data, $result));
        }

        if ($meta->hasRelation())
        {
            // 子模型更新
            ModelRelationManager::updateModel($this);
        }

        $this->__originData = array_merge($this->__originData, ObjectArrayHelper::toArray($data));

        return $result;
    }

    /**
     * 保存记录.
     */
    public function save(): IResult
    {
        $meta = $this->__meta;

        $recordExists = $this->__recordExists;
        // 当有自增字段时，根据自增字段值处理
        if (null === $recordExists)
        {
            $autoIncrementField = $meta->getAutoIncrementField();
            if (null !== $autoIncrementField)
            {
                $recordExists = ($this[$autoIncrementField] ?? 0) > 0;
            }
        }
        else
        {
            $autoIncrementField = null;
        }

        $data = self::parseSaveData(iterator_to_array($this), 'save', $this);
        $isBean = $meta->isBean();
        $query = static::query();

        if ($isBean)
        {
            // 保存前
            $this->dispatch(new BeforeSaveEventParam($this, $data, $query));
        }

        if (true === $recordExists)
        {
            $result = $this->__update($data);
        }
        elseif (false === $recordExists)
        {
            $result = $this->__insert($data);
        }
        else
        {
            foreach (static::PRIMARY_KEYS ?? $meta->getId() as $idName)
            {
                if (isset($data[$idName]))
                {
                    $query->where($idName, '=', $data[$idName]);
                }
                elseif (isset($this[$idName]))
                {
                    $query->where($idName, '=', $this[$idName]);
                }
            }
            $result = $query->replace($data, $meta->getId() ?? []);
            if ($result->isSuccess() && $autoIncrementField)
            {
                $this[$autoIncrementField] = $result->getLastInsertId();
            }
        }
        $this->__originData = array_merge($this->__originData, ObjectArrayHelper::toArray($data));
        $this->__recordExists = true;

        if ($isBean)
        {
            // 保存后
            $this->dispatch(new AfterSaveEventParam($this, $data, $result));
        }

        return $result;
    }

    /**
     * 删除记录.
     */
    public function delete(): IResult
    {
        $query = static::query();
        $meta = $this->__meta;
        $isBean = $meta->isBean();

        if ($isBean)
        {
            // 删除前
            $this->dispatch(new BeforeDeleteEventParam($this, $query));
        }

        $hasIdWhere = false;
        foreach (static::PRIMARY_KEYS ?? $meta->getId() as $idName)
        {
            if (isset($this[$idName]))
            {
                $query->where($idName, '=', $this[$idName]);
                $hasIdWhere = true;
            }
        }
        if (!$hasIdWhere)
        {
            throw new \RuntimeException('Use Model->delete(), primary key can not be null');
        }
        $result = $query->delete();

        if ($isBean)
        {
            // 删除后
            $this->dispatch(new AfterDeleteEventParam($this, $result));
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
     * 查询指定关联.
     */
    public function queryRelations(string ...$names): void
    {
        ModelRelationManager::queryModelRelations($this, ...$names);

        // 关联字段加入序列化
        if ($this->__serializedFields)
        {
            $this->__serializedFields = array_merge($this->__serializedFields, $names);
        }
        else
        {
            $this->__serializedFields = array_merge($this->__fieldNames, $names);
        }
    }

    /**
     * 为一个列表查询指定关联.
     */
    public static function queryRelationsList(iterable $list, string ...$names): iterable
    {
        if ($list)
        {
            ModelRelationManager::initModels($list, $names);
            /** @var self $model */
            $model = $list[0];
            $__serializedFields = $model->__serializedFields;
            // 关联字段加入序列化
            if ($__serializedFields)
            {
                $__serializedFields = array_merge($__serializedFields, $names);
            }
            else
            {
                $__serializedFields = array_merge($model->__fieldNames, $names);
            }
        }

        return $list;
    }

    /**
     * 统计数量.
     */
    public static function count(string $field = '*'): int
    {
        return static::aggregate('count', $field);
    }

    /**
     * 求和.
     *
     * @return int|float
     */
    public static function sum(string $field)
    {
        return static::aggregate('sum', $field);
    }

    /**
     * 平均值
     *
     * @return int|float
     */
    public static function avg(string $field)
    {
        return static::aggregate('avg', $field);
    }

    /**
     * 最大值
     *
     * @return int|float
     */
    public static function max(string $field)
    {
        return static::aggregate('max', $field);
    }

    /**
     * 最小值
     *
     * @return int|float
     */
    public static function min(string $field)
    {
        return static::aggregate('min', $field);
    }

    /**
     * 聚合函数.
     */
    public static function aggregate(string $functionName, string $fieldName, ?callable $queryCallable = null): mixed
    {
        $query = static::query();
        if (null !== $queryCallable)
        {
            // 回调传入条件
            $queryCallable($query);
        }

        return $query->{$functionName}($fieldName);
    }

    /**
     * Fork 模型.
     *
     * @return class-string<static>
     */
    public static function fork(?string $tableName = null, ?string $poolName = null)
    {
        $forks = &self::$__forks;
        if (isset($forks[static::class][$tableName][$poolName]))
        {
            return $forks[static::class][$tableName][$poolName];
        }
        $namespace = Imi::getClassNamespace(static::class);
        if (null === $tableName)
        {
            $setTableName = '';
        }
        else
        {
            $setTableName = '$meta->setTableName(\'' . addcslashes($tableName, '\'\\') . '\');';
        }
        if (null === $poolName)
        {
            $setPoolName = '';
        }
        else
        {
            $setPoolName = '$meta->setDbPoolName(\'' . addcslashes($poolName, '\'\\') . '\');';
        }
        $class = str_replace('\\', '__', static::class . '\\' . md5($tableName . '\\' . $poolName));
        $extendsClass = static::class;
        Imi::eval(<<<PHP
        namespace {$namespace} {
            class {$class} extends \\{$extendsClass}
            {
                public static function __getMeta(\$object = null): \Imi\Model\Meta
                {
                    if (\$object)
                    {
                        \$class = \Imi\Bean\BeanFactory::getObjectClass(\$object);
                    }
                    else
                    {
                        \$class = static::__getRealClassName();
                    }
                    \$__metas = &self::\$__metas;
                    if (isset(\$__metas[\$class]))
                    {
                        \$meta = \$__metas[\$class];
                    }
                    else
                    {
                        \$meta = \$__metas[\$class] = new \Imi\Model\Meta(\$class, true);
                    }
                    if (static::class === \$class || is_subclass_of(\$class, static::class))
                    {
                        {$setTableName}
                        {$setPoolName}
                    }

                    return \$meta;
                }
            }
        }
        PHP);

        return $forks[static::class][$tableName][$poolName] = $namespace . '\\' . $class;
    }

    /**
     * 从记录创建模型对象
     *
     * @return static
     */
    public static function createFromRecord(array $data, bool $queryRelation = true): self
    {
        $model = static::newInstance($data, $queryRelation);
        $model->__recordExists = true;

        return $model;
    }

    /**
     * 处理where条件.
     */
    private static function parseWhere(IQuery $query, mixed $where): void
    {
        if (\is_callable($where))
        {
            // 回调传入条件
            $where($query);
        }
        elseif ($where)
        {
            foreach ($where as $k => $v)
            {
                if (\is_array($v))
                {
                    $operation = array_shift($v);
                    $query->where($k, $operation, $v[0]);
                }
                else
                {
                    $query->where($k, '=', $v);
                }
            }
        }
    }

    /**
     * @param bool|int $timeAccuracy 推荐最大精度6位（微秒），部分系统能提供9位精度（纳秒）
     */
    protected static function parseDateTime(?string $columnType, bool|int $timeAccuracy, ?float $microTime = null): int|string|null
    {
        $microTime ??= microtime(true);

        return match ($columnType)
        {
            'date' => date('Y-m-d', (int) $microTime),
            'time' => date('H:i:s', (int) $microTime),
            'datetime', 'timestamp' => date('Y-m-d H:i:s', (int) $microTime),
            'int'    => (int) $microTime,
            'bigint' => (int) ($microTime * (true === $timeAccuracy ? 1000 : $timeAccuracy)),
            'year'   => (int) date('Y', (int) $microTime),
            default  => null,
        };
    }

    /**
     * 处理保存的数据.
     */
    private static function parseSaveData(object|array $data, string $type, ?self $object = null): LazyArrayObject
    {
        $meta = static::__getMeta($object);
        $realClassName = static::__getRealClassName();
        // 处理前
        $event = new \Imi\Model\Event\Param\BeforeParseDataEventParam($realClassName . '.' . ModelEvents::BEFORE_PARSE_DATA, $data, $object);
        Event::dispatch($event);
        $data = $event->data;
        $object = $event->object;

        if (\is_object($data))
        {
            $_data = [];
            foreach ($data as $k => $v)
            {
                $_data[$k] = $v;
            }
            $data = $_data;
        }
        $result = [];
        $isInsert = 'insert' === $type;
        $isUpdate = 'update' === $type;
        $isSave = 'save' === $type;
        $isSaveInsert = $isSave && $object && !$object->__recordExists;
        $isSaveUpdate = $isSave && $object && $object->__recordExists;
        if ($object)
        {
            $rawValues = $object->__rawValues;
            $object->__rawValues = [];
            $originData = $object->__originData;
        }
        else
        {
            $rawValues = null;
            $originData = [];
        }
        $incrUpdate = $meta->isIncrUpdate();
        $ids = $meta->getIds();
        $propertyAnnotations = $meta->getPropertyAnnotations();
        foreach ($meta->getDbFields() as $dbFieldName => $item)
        {
            /** @var Column $column */
            ['propertyName' => $name, 'column' => $column] = $item;
            // 虚拟字段不参与数据库操作
            if ($column->virtual)
            {
                continue;
            }
            if ($rawValues)
            {
                if (isset($rawValues[$name]))
                {
                    $result[$dbFieldName] = new Raw($rawValues[$name]);
                    continue;
                }
                if (isset($rawValues[$dbFieldName]))
                {
                    $result[$dbFieldName] = new Raw($rawValues[$dbFieldName]);
                    continue;
                }
            }
            $columnType = $column->type;
            // 字段自动更新时间
            if ((($updateTimeAnnotation = $propertyAnnotations[UpdateTime::class][$name][0] ?? null) || $column->updateTime) && !$isInsert && (empty($object[$dbFieldName]) || (($originData[$dbFieldName] ?? null) === $object[$dbFieldName])))
            {
                $microTime ??= microtime(true);
                /** @var UpdateTime|null $updateTimeAnnotation */
                $value = static::parseDateTime($columnType, $updateTimeAnnotation?->timeAccuracy ?? $column->updateTime, $microTime);
                if (null === $value)
                {
                    throw new \RuntimeException(sprintf('Column %s type is %s, can not updateTime', $dbFieldName, $columnType));
                }
                if ($object)
                {
                    $object[$dbFieldName] = $value;
                }
            }
            elseif ((($createTimeAnnotation = $propertyAnnotations[CreateTime::class][$name][0] ?? null) || $column->createTime) && ($isInsert || $isSaveInsert) && empty($object[$dbFieldName]))
            {
                $microTime ??= microtime(true);
                /** @var CreateTime|null $createTimeAnnotation */
                $value = static::parseDateTime($columnType, $createTimeAnnotation?->timeAccuracy ?? $column->createTime, $microTime);
                if (null === $value)
                {
                    throw new \RuntimeException(sprintf('Column %s type is %s, can not createTime', $dbFieldName, $columnType));
                }
                if ($object)
                {
                    $object[$dbFieldName] = $value;
                }
            }
            elseif (($isInsert || $isSaveInsert) && isset($ids[$name]) && '' !== $ids[$name]->generator)
            {
                $value = App::getBean($ids[$name]->generator)->generate($object, $ids[$name]->generatorOptions);
                if ($object)
                {
                    $object[$dbFieldName] = $value;
                }
            }
            elseif (\array_key_exists($name, $data))
            {
                $value = $data[$name];
            }
            elseif (\array_key_exists($dbFieldName, $data))
            {
                $value = $data[$dbFieldName];
            }
            else
            {
                if ($isUpdate)
                {
                    continue;
                }
                $value = null;
            }
            if (null === $value && !$column->nullable && !\in_array($columnType, static::$_fieldParseNullTypes))
            {
                continue;
            }
            if (isset(static::$__fieldSaveParsers[$columnType]))
            {
                $value = self::{static::$__fieldSaveParsers[$columnType]}($name, $value, $column, $meta);
            }
            if ($incrUpdate && (!$isInsert || $isSaveUpdate) && ((\array_key_exists($dbFieldName, $originData) && $originData[$dbFieldName] === $value) || (\array_key_exists($name, $originData) && $originData[$name] === $value)))
            {
                continue;
            }
            $result[$dbFieldName] = $value;
        }

        // 更新时无需更新主键
        if ($isUpdate || $isSaveUpdate)
        {
            foreach (static::PRIMARY_KEYS ?? $meta->getId() as $id)
            {
                if (isset($result[$id]))
                {
                    unset($result[$id]);
                }
            }
        }

        $result = new LazyArrayObject($result);
        // 处理后
        $event = new \Imi\Model\Event\Param\AfterParseDataEventParam($realClassName . '.' . ModelEvents::AFTER_PARSE_DATA, $data, $object, $result);
        Event::dispatch($event);

        return $event->result;
    }

    /**
     * 设置字段的值为 sql，如果为null则清除设置.
     */
    public function __setRaw(string $field, ?string $sql): self
    {
        $this->__rawValues[$field] = $sql;

        return $this;
    }

    /**
     * 获取设置字段的 sql 值
     */
    public function __getRaw(string $field): ?string
    {
        return $this->__rawValues[$field] ?? null;
    }

    public function __serialize(): array
    {
        $result = parent::__serialize();
        $result['rawValues'] = $this->__rawValues;

        return $result;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        ['rawValues' => $this->__rawValues] = $data;
    }
}
