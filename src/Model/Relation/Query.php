<?php

declare(strict_types=1);

namespace Imi\Model\Relation;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\BeanFactory;
use Imi\Db\Db;
use Imi\Db\Query\Field;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Event\Event;
use Imi\Model\Annotation\Relation\AutoSelect;
use Imi\Model\Annotation\Relation\RelationBase;
use Imi\Model\Model;
use Imi\Model\Relation\Struct\ManyToMany;
use Imi\Model\Relation\Struct\OneToMany;
use Imi\Model\Relation\Struct\OneToOne;
use Imi\Model\Relation\Struct\PolymorphicManyToMany;
use Imi\Model\Relation\Struct\PolymorphicOneToMany;
use Imi\Model\Relation\Struct\PolymorphicOneToOne;
use Imi\Util\ArrayList;
use Imi\Util\ClassObject;
use Imi\Util\Imi;

class Query
{
    private function __construct()
    {
    }

    /**
     * 初始化.
     *
     * @param \Imi\Bean\Annotation\Base[] $annotations
     * @param bool                        $forceInit   是否强制更新
     */
    public static function init(Model $model, string $propertyName, array $annotations, bool $forceInit = false): void
    {
        $className = BeanFactory::getObjectClass($model);

        if (!$forceInit)
        {
            /** @var AutoSelect|null $autoSelect */
            $autoSelect = AnnotationManager::getPropertyAnnotations($className, $propertyName, AutoSelect::class)[0] ?? null;
            if ($autoSelect && !$autoSelect->status)
            {
                return;
            }
        }

        $firstAnnotation = reset($annotations);

        // @phpstan-ignore-next-line
        if ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicToOne)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicToOne($model, $propertyName, $annotations);
        }
        // @phpstan-ignore-next-line
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicOneToOne)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicOneToOne($model, $propertyName, $firstAnnotation);
        }
        // @phpstan-ignore-next-line
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicOneToMany)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicOneToMany($model, $propertyName, $firstAnnotation);
        }
        // @phpstan-ignore-next-line
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicToMany)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicToMany($model, $propertyName, $annotations);
        }
        // @phpstan-ignore-next-line
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicManyToMany)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicManyToMany($model, $propertyName, $firstAnnotation);
        }
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\OneToOne)
        {
            static::initByOneToOne($model, $propertyName, $firstAnnotation);
        }
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\OneToMany)
        {
            static::initByOneToMany($model, $propertyName, $firstAnnotation);
        }
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\ManyToMany)
        {
            static::initByManyToMany($model, $propertyName, $firstAnnotation);
        }
    }

    /**
     * 初始化一对一关系.
     */
    public static function initByOneToOne(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\OneToOne $annotation): void
    {
        $className = BeanFactory::getObjectClass($model);

        if (class_exists($annotation->model))
        {
            $modelClass = $annotation->model;
        }
        else
        {
            $modelClass = Imi::getClassNamespace($className) . '\\' . $annotation->model;
        }

        $struct = new OneToOne($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        if (null === $model->$leftField)
        {
            $rightModel = $modelClass::newInstance();
        }
        else
        {
            /** @var IQuery $query */
            $query = $modelClass::query()->where($rightField, '=', $model->$leftField);
            if ($annotation->fields)
            {
                $query->field(...$annotation->fields);
            }
            Event::trigger($eventName . '.BEFORE', [
                'model'        => $model,
                'propertyName' => $propertyName,
                'annotation'   => $annotation,
                'struct'       => $struct,
                'query'        => $query,
            ]);
            $rightModel = $query->select()->get();
            if (null === $rightModel)
            {
                $rightModel = $modelClass::newInstance();
            }
        }

        $model->$propertyName = $rightModel;
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化一对多关系.
     */
    public static function initByOneToMany(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\OneToMany $annotation): void
    {
        $className = BeanFactory::getObjectClass($model);

        if (class_exists($annotation->model))
        {
            $modelClass = $annotation->model;
        }
        else
        {
            $modelClass = Imi::getClassNamespace($className) . '\\' . $annotation->model;
        }

        $struct = new OneToMany($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        $model->$propertyName = new ArrayList($modelClass);
        if (null !== $model->$leftField)
        {
            /** @var IQuery $query */
            $query = $modelClass::query()->where($rightField, '=', $model->$leftField);
            if ($annotation->fields)
            {
                $query->field(...$annotation->fields);
            }
            if ($annotation->order)
            {
                $query->orderRaw($annotation->order);
            }
            if (null !== $annotation->limit)
            {
                $query->limit($annotation->limit);
            }
            Event::trigger($eventName . '.BEFORE', [
                'model'        => $model,
                'propertyName' => $propertyName,
                'annotation'   => $annotation,
                'struct'       => $struct,
                'query'        => $query,
            ]);
            $list = $query->select()->getArray();
            if (null !== $list)
            {
                $model->$propertyName->append(...$list);
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化多对多关系.
     */
    public static function initByManyToMany(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\ManyToMany $annotation): void
    {
        $className = BeanFactory::getObjectClass($model);

        $struct = new ManyToMany($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();
        $middleModel = $struct->getMiddleModel();
        $middleTable = $middleModel::__getMeta()->getFullTableName();
        $rightModel = $struct->getRightModel();
        $rightTable = $rightModel::__getMeta()->getFullTableName();

        $fields = static::parseManyToManyQueryFields($middleModel, $rightModel);

        $model->$propertyName = new ArrayList($middleModel);
        $model->{$annotation->rightMany} = new ArrayList($rightModel);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        if (null !== $model->$leftField)
        {
            $query = Db::query($className::__getMeta()->getDbPoolName())
                        ->table($rightTable)
                        ->field(...$fields)
                        ->join($middleTable, $middleTable . '.' . $struct->getMiddleRightField(), '=', $rightTable . '.' . $rightField)
                        ->where($middleTable . '.' . $struct->getMiddleLeftField(), '=', $model->$leftField);
            if ($annotation->order)
            {
                $query->orderRaw($annotation->order);
            }
            if (null !== $annotation->limit)
            {
                $query->limit($annotation->limit);
            }
            Event::trigger($eventName . '.BEFORE', [
                'model'        => $model,
                'propertyName' => $propertyName,
                'annotation'   => $annotation,
                'struct'       => $struct,
                'query'        => $query,
            ]);
            $list = $query->select()
                          ->getArray();
            if (null !== $list)
            {
                // 关联数据
                static::appendMany($model->$propertyName, $list, $middleTable, $middleModel);

                // 右侧表数据
                static::appendMany($model->{$annotation->rightMany}, $list, $rightTable, $rightModel);
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化多态一对一关系.
     */
    public static function initByPolymorphicOneToOne(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\PolymorphicOneToOne $annotation): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        if (class_exists($annotation->model))
        {
            $modelClass = $annotation->model;
        }
        else
        {
            $modelClass = Imi::getClassNamespace($className) . '\\' . $annotation->model;
        }
        $struct = new PolymorphicOneToOne($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();

        if (null === $model->$leftField)
        {
            $rightModel = $modelClass::newInstance();
        }
        else
        {
            /** @var IQuery $query */
            $query = $modelClass::query()->where($annotation->type, '=', $annotation->typeValue)->where($rightField, '=', $model->$leftField);
            if ($annotation->fields)
            {
                $query->field(...$annotation->fields);
            }
            Event::trigger($eventName . '.BEFORE', [
                'model'        => $model,
                'propertyName' => $propertyName,
                'annotation'   => $annotation,
                'struct'       => $struct,
                'query'        => $query,
            ]);
            $rightModel = $query->select()->get();
            if (null === $rightModel)
            {
                $rightModel = $modelClass::newInstance();
            }
        }

        $model->$propertyName = $rightModel;
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化多态一对多关系.
     */
    public static function initByPolymorphicOneToMany(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\PolymorphicOneToMany $annotation): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        if (class_exists($annotation->model))
        {
            $modelClass = $annotation->model;
        }
        else
        {
            $modelClass = Imi::getClassNamespace($className) . '\\' . $annotation->model;
        }

        $struct = new PolymorphicOneToMany($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();

        $model->$propertyName = $modelPropery = new ArrayList($modelClass);
        if (null !== $model->$leftField)
        {
            /** @var IQuery $query */
            $query = $modelClass::query()->where($annotation->type, '=', $annotation->typeValue)->where($rightField, '=', $model->$leftField);
            if ($annotation->fields)
            {
                $query->field(...$annotation->fields);
            }
            if ($annotation->order)
            {
                $query->orderRaw($annotation->order);
            }
            if (null !== $annotation->limit)
            {
                $query->limit($annotation->limit);
            }
            Event::trigger($eventName . '.BEFORE', [
                'model'        => $model,
                'propertyName' => $propertyName,
                'annotation'   => $annotation,
                'struct'       => $struct,
                'query'        => $query,
            ]);
            $list = $query->select()->getArray();
            if (null !== $list)
            {
                $modelPropery->append(...$list);
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化多态，对应的实体模型.
     *
     * @param \Imi\Model\Annotation\Relation\PolymorphicToOne[] $annotations
     */
    public static function initByPolymorphicToOne(Model $model, string $propertyName, array $annotations): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;
        foreach ($annotations as $annotationItem)
        {
            if ($model->{$annotationItem->type} == $annotationItem->typeValue)
            {
                $leftField = $annotationItem->modelField;
                $rightField = $annotationItem->field;
                if (class_exists($annotationItem->model))
                {
                    $modelClass = $annotationItem->model;
                }
                else
                {
                    $modelClass = $className . '\\' . $annotationItem->model;
                }
                if (null === $model->$rightField)
                {
                    $leftModel = $modelClass::newInstance();
                }
                else
                {
                    /** @var IQuery $query */
                    $query = $modelClass::query()->where($leftField, '=', $model->$rightField);
                    if ($annotationItem->fields)
                    {
                        $query->field(...$annotationItem->fields);
                    }
                    Event::trigger($eventName . '.BEFORE', [
                        'model'        => $model,
                        'propertyName' => $propertyName,
                        'annotation'   => $annotationItem,
                        'query'        => $query,
                    ]);
                    $leftModel = $query->select()->get();
                    if (null === $leftModel)
                    {
                        $leftModel = $modelClass::newInstance();
                    }
                }
                $model->$propertyName = $leftModel;
                break;
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => isset($leftModel) ? ($annotationItem ?? null) : null,
        ]);
    }

    /**
     * 初始化多态，对应的实体模型列表.
     *
     * @param \Imi\Model\Annotation\Relation\PolymorphicToMany[] $annotations
     */
    public static function initByPolymorphicToMany(Model $model, string $propertyName, array $annotations): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        foreach ($annotations as $annotationItem)
        {
            if ($model->{$annotationItem->type} == $annotationItem->typeValue)
            {
                $struct = new PolymorphicManyToMany($className, $propertyName, $annotationItem);
                $leftField = $struct->getLeftField();
                $rightField = $struct->getRightField();
                $middleTable = $struct->getMiddleModel()::__getMeta()->getFullTableName();
                $rightTable = $struct->getRightModel()::__getMeta()->getFullTableName();

                $fields = static::parseManyToManyQueryFields($struct->getMiddleModel(), $struct->getRightModel());

                $model->$propertyName = new ArrayList($struct->getRightModel());

                if (null !== $model->$leftField)
                {
                    $query = Db::query($className::__getMeta()->getDbPoolName())
                                ->table($rightTable)
                                ->field(...$fields)
                                ->join($middleTable, $middleTable . '.' . $struct->getMiddleLeftField(), '=', $rightTable . '.' . $rightField)
                                ->where($middleTable . '.' . $annotationItem->type, '=', $annotationItem->typeValue)
                                ->where($middleTable . '.' . $struct->getMiddleRightField(), '=', $model->$leftField);
                    if ($annotationItem->order)
                    {
                        $query->orderRaw($annotationItem->order);
                    }
                    if (null !== $annotationItem->limit)
                    {
                        $query->limit($annotationItem->limit);
                    }
                    Event::trigger($eventName . '.BEFORE', [
                        'model'        => $model,
                        'propertyName' => $propertyName,
                        'annotation'   => $annotationItem,
                        'struct'       => $struct,
                        'query'        => $query,
                    ]);
                    $list = $query->select()
                                  ->getArray();
                    if (null !== $list)
                    {
                        // 关联数据
                        static::appendMany($model->$propertyName, $list, $rightTable, $struct->getRightModel());
                    }
                }
                break;
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => isset($struct) ? ($annotationItem ?? null) : null,
            'struct'       => $struct ?? null,
        ]);
    }

    /**
     * 初始化多态多对多关系.
     */
    public static function initByPolymorphicManyToMany(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\PolymorphicManyToMany $annotation): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        $struct = new PolymorphicManyToMany($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();
        $middleTable = $struct->getMiddleModel()::__getMeta()->getFullTableName();
        $rightTable = $struct->getRightModel()::__getMeta()->getFullTableName();

        $fields = static::parseManyToManyQueryFields($struct->getMiddleModel(), $struct->getRightModel());

        $model->$propertyName = new ArrayList($struct->getMiddleModel());
        $model->{$annotation->rightMany} = new ArrayList($struct->getRightModel());

        if (null !== $model->$leftField)
        {
            $query = Db::query($className::__getMeta()->getDbPoolName())
                        ->table($rightTable)
                        ->field(...$fields)
                        ->join($middleTable, $middleTable . '.' . $struct->getMiddleRightField(), '=', $rightTable . '.' . $rightField)
                        ->where($middleTable . '.' . $annotation->type, '=', $annotation->typeValue)
                        ->where($middleTable . '.' . $struct->getMiddleLeftField(), '=', $model->$leftField);

            if (null !== $annotation->limit)
            {
                $query->limit($annotation->limit);
            }
            Event::trigger($eventName . '.BEFORE', [
                'model'        => $model,
                'propertyName' => $propertyName,
                'annotation'   => $annotation,
                'struct'       => $struct,
                'query'        => $query,
            ]);
            $list = $query->select()
                            ->getArray();
            if (null !== $list)
            {
                // 关联数据
                static::appendMany($model->$propertyName, $list, $middleTable, $struct->getMiddleModel());

                // 右侧表数据
                static::appendMany($model->{$annotation->rightMany}, $list, $rightTable, $struct->getRightModel());
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化关联属性.
     */
    public static function initRelations(Model $model, string $propertyName): void
    {
        $className = BeanFactory::getObjectClass($model);
        $annotation = AnnotationManager::getPropertyAnnotations($className, $propertyName, RelationBase::class)[0] ?? null;
        if (null !== $annotation)
        {
            if ($annotation instanceof \Imi\Model\Annotation\Relation\OneToOne)
            {
                $model->$propertyName = (ClassObject::parseSameLevelClassName($annotation->model, $className) . '::newInstance')();
            }
            elseif ($annotation instanceof \Imi\Model\Annotation\Relation\OneToMany)
            {
                $model->$propertyName = new ArrayList(ClassObject::parseSameLevelClassName($annotation->model, $className));
            }
            elseif ($annotation instanceof \Imi\Model\Annotation\Relation\ManyToMany)
            {
                $model->$propertyName = new ArrayList(ClassObject::parseSameLevelClassName($annotation->middle, $className));
            }
            elseif ($annotation instanceof \Imi\Model\Annotation\Relation\PolymorphicOneToOne)
            {
                $model->$propertyName = (ClassObject::parseSameLevelClassName($annotation->model, $className) . '::newInstance')();
            }
            elseif ($annotation instanceof \Imi\Model\Annotation\Relation\PolymorphicOneToMany)
            {
                $model->$propertyName = new ArrayList(ClassObject::parseSameLevelClassName($annotation->model, $className));
            }
            // @phpstan-ignore-next-line
            elseif ($annotation instanceof \Imi\Model\Annotation\Relation\PolymorphicManyToMany)
            {
                $model->$propertyName = new ArrayList(ClassObject::parseSameLevelClassName($annotation->middle, $className));
            }
            else
            {
                return;
            }
        }
    }

    /**
     * 处理多对多查询用的字段，需要是"表名.字段名"，防止冲突
     */
    private static function parseManyToManyQueryFields(string $middleModel, string $rightModel): array
    {
        $fields = [];

        /** @var \Imi\Model\Meta $middleModelMeta */
        $middleModelMeta = $middleModel::__getMeta();
        $middleTable = $middleModelMeta->getFullTableName();
        /** @var \Imi\Model\Meta $rightModelMeta */
        $rightModelMeta = $rightModel::__getMeta();
        $rightTable = $rightModelMeta->getFullTableName();

        foreach ($middleModelMeta->getDbFields() as $name => $_)
        {
            $fields[] = $field = new Field();
            $field->setTable($middleTable);
            $field->setField($name);
            $field->setAlias($middleTable . '_' . $name);
        }
        foreach ($middleModelMeta->getSqlColumns() as $name => $sqlAnnotations)
        {
            /** @var \Imi\Model\Annotation\Sql $sqlAnnotation */
            $sqlAnnotation = $sqlAnnotations[0];
            $fields[] = $field = new Field();
            $field->useRaw();
            $field->setRawSQL($sqlAnnotation->sql);
            $field->setAlias($middleTable . '_' . $name);
        }

        foreach ($rightModelMeta->getDbFields() as $name => $_)
        {
            $fields[] = $field = new Field();
            $field->setTable($rightTable);
            $field->setField($name);
            $field->setAlias($rightTable . '_' . $name);
        }
        foreach ($rightModelMeta->getSqlColumns() as $name => $sqlAnnotations)
        {
            /** @var \Imi\Model\Annotation\Sql $sqlAnnotation */
            $sqlAnnotation = $sqlAnnotations[0];
            $fields[] = $field = new Field();
            $field->useRaw();
            $field->setRawSQL($sqlAnnotation->sql);
            $field->setAlias($rightTable . '_' . $name);
        }

        return $fields;
    }

    /**
     * 追加到Many列表.
     */
    private static function appendMany(ArrayList $manyList, array $dataList, string $table, string $modelClass): void
    {
        $tableLength = \strlen($table);
        $keysMap = [];
        foreach ($dataList as $row)
        {
            $tmpRow = [];
            foreach ($row as $key => $value)
            {
                if (isset($keysMap[$key]))
                {
                    if (false !== $keysMap[$key])
                    {
                        $tmpRow[$keysMap[$key]] = $value;
                    }
                }
                elseif (str_starts_with($key, $table))
                {
                    $keysMap[$key] = $realKey = substr($key, $tableLength);
                    $tmpRow[$realKey] = $value;
                }
                else
                {
                    $keysMap[$key] = false;
                }
            }
            $manyList->append($modelClass::createFromRecord($tmpRow));
        }
    }
}
