<?php

declare(strict_types=1);

namespace Imi\Model;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\Annotation\Base;
use Imi\Config;
use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\Entity;
use Imi\Model\Annotation\Id;
use Imi\Model\Annotation\Serializable;
use Imi\Model\Annotation\Serializables;
use Imi\Model\Annotation\Table;
use Imi\Util\Text;

/**
 * 模型元数据.
 */
class Meta
{
    /**
     * 类名.
     */
    private string $className = '';

    /**
     * 数据库名.
     */
    private ?string $databaseName = null;

    /**
     * 表名.
     */
    private ?string $tableName = null;

    /**
     * 使用表名前缀
     */
    private bool $usePrefix = false;

    /**
     * 数据库连接池名称.
     */
    private ?string $dbPoolName = null;

    /**
     * 主键.
     */
    private ?array $id = null;

    /**
     * @var Id[]
     */
    private array $ids = [];

    /**
     * 第一个主键.
     */
    private ?string $firstId = null;

    /**
     * 所有字段配置.
     *
     * @var \Imi\Model\Annotation\Column[]
     */
    private array $fields = [];

    /**
     * 所有字段属性名列表.
     *
     * @var string[]
     */
    private array $fieldNames = [];

    /**
     * 序列化后的所有字段属性名列表.
     *
     * @var string[]
     */
    private array $serializableFieldNames = [];

    /**
     * 数据库字段名和 Column 注解映射.
     */
    private array $dbFields = [];

    /**
     * 模型是否为驼峰命名.
     */
    private bool $camel = true;

    /**
     * 是否有关联.
     */
    private bool $relation = false;

    /**
     * 自增字段名.
     */
    private ?string $autoIncrementField = null;

    /**
     * 真实的模型类名.
     */
    private string $realModelClass = '';

    /**
     * 模型对象是否作为 bean 类使用.
     */
    private bool $bean = false;

    /**
     * 处理后的序列化字段数组.
     *
     * 已包含注解：Serializable、Serializables
     */
    private array $parsedSerializableFieldNames = [];

    /**
     * 是否启用增量更新.
     */
    private bool $incrUpdate = false;

    /**
     * 类注解集合.
     *
     * @var \Imi\Bean\Annotation\Base[][]
     */
    private array $classAnnotations = [];

    /**
     * 属性注解集合.
     *
     * @var \Imi\Bean\Annotation\Base[][][]
     */
    private array $propertyAnnotations = [];

    public function __construct(string $modelClass,
        /**
         * 是否为继承父类的模型.
         */
        private readonly bool $inherit = false)
    {
        if ($inherit)
        {
            $realModelClass = get_parent_class($modelClass);
        }
        else
        {
            $realModelClass = $modelClass;
        }
        $modelConfig = Config::get('@app.models.' . $realModelClass);
        $this->realModelClass = $realModelClass;
        $this->className = $modelClass;
        // 类注解
        $classAnnotations = [];
        /** @var Base $annotation */
        foreach (AnnotationManager::getClassAnnotations($realModelClass) as $annotation)
        {
            $classAnnotations[$annotation::class][] = $annotation;
        }
        $this->classAnnotations = $classAnnotations;
        // 属性注解
        $propertyAnnotations = [];
        foreach (AnnotationManager::getPropertiesAnnotations($realModelClass) as $propertyName => $annotations)
        {
            foreach ($annotations as $annotation)
            {
                $propertyAnnotations[$annotation::class][$propertyName][] = $annotation;
            }
        }
        $this->propertyAnnotations = $propertyAnnotations;
        if ($table = $classAnnotations[Table::class][0] ?? null)
        {
            /** @var \Imi\Model\Annotation\Table|null $table */
            $this->dbPoolName = $modelConfig['poolName'] ?? $table->dbPoolName;
            $this->id = $id = (array) $table->id;
            $this->setTableName($modelConfig['name'] ?? $table->name);
            $this->usePrefix = $modelConfig['prefix'] ?? $table->usePrefix;
        }
        else
        {
            $id = [];
        }
        if ($ids = ($propertyAnnotations[Id::class] ?? null))
        {
            $setToId = !$id;
            /** @var Id[] $propertyIds */
            foreach ($ids as $name => $propertyIds)
            {
                $this->ids[$name] = $propertyId = $propertyIds[0];
                if ($setToId && false !== ($index = $propertyId->index))
                {
                    /** @var Column|null $column */
                    if ($column = $propertyAnnotations[Column::class][$name] ?? null)
                    {
                        if (null === $index)
                        {
                            $id[] = $column->name ?? $name;
                        }
                        else
                        {
                            $id[$index] = $column->name ?? $name;
                        }
                    }
                }
            }
            ksort($id);
            $this->id = $id;
        }
        $this->firstId = $id[0] ?? null;
        /** @var Column[] $fields */
        $fields = $dbFields = [];
        foreach ($propertyAnnotations[Column::class] ?? [] as $name => $columns)
        {
            /** @var Column $column */
            $column = $columns[0];
            if (null !== $column->name)
            {
                $dbFields[$column->name] = [
                    'propertyName' => $name,
                    'column'       => $column,
                ];
            }
            $fields[$name] = $column;
            if (null === $this->autoIncrementField && !$column->virtual && $column->isAutoIncrement)
            {
                $this->autoIncrementField = $name;
            }
        }
        $this->relation = $relation = ModelRelationManager::hasRelation($realModelClass);
        if ($relation)
        {
            foreach (ModelRelationManager::getRelationFieldNames($realModelClass) as $name)
            {
                if (!isset($fields[$name]))
                {
                    $fields[$name] = new Column(virtual: true);
                }
            }
        }
        $this->dbFields = $dbFields;
        $this->fields = $fields;
        /** @var \Imi\Model\Annotation\Entity|null $entity */
        $entity = $classAnnotations[Entity::class][0] ?? null;
        $this->camel = $camel = $entity->camel ?? true;
        $this->bean = $entity->bean ?? true;
        $this->incrUpdate = $entity->incrUpdate ?? false;
        $serializableFieldNames = $parsedSerializableFieldNames = $fieldNames = [];
        $serializableSets = $propertyAnnotations[Serializable::class] ?? [];
        foreach ($fields as $fieldName => $column)
        {
            $fieldNames[] = $fieldName;
            if ($camel)
            {
                $name = Text::toCamelName($fieldName);
            }
            elseif ($column->virtual)
            {
                $name = $fieldName;
            }
            else
            {
                $name = $column->name;
            }
            $serializableFieldNames[$fieldName] = $name;

            if (isset($serializableSets[$fieldName]))
            {
                // 单独属性上的 @Serializable 注解
                if (!$serializableSets[$fieldName][0]->allow)
                {
                    continue;
                }
            }
            elseif ($serializables = $classAnnotations[Serializables::class][0] ?? null)
            {
                /** @var Serializables $serializables */
                if (\in_array($name, $serializables->fields))
                {
                    // 在黑名单中的字段剔除
                    if ('deny' === $serializables->mode)
                    {
                        continue;
                    }
                }
                else
                {
                    // 不在白名单中的字段剔除
                    if ('allow' === $serializables->mode)
                    {
                        continue;
                    }
                }
            }
            $parsedSerializableFieldNames[] = $name;
        }
        $this->serializableFieldNames = $serializableFieldNames;
        $this->parsedSerializableFieldNames = $parsedSerializableFieldNames;
        $this->fieldNames = $fieldNames;
    }

    /**
     * Get 数据库名.
     */
    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    /**
     * Get 表名.
     */
    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    /**
     * 获取完整表名.
     */
    public function getFullTableName(): ?string
    {
        if (null === $this->tableName)
        {
            return null;
        }
        if (null === $this->databaseName)
        {
            return $this->tableName;
        }

        return $this->databaseName . '.' . $this->tableName;
    }

    /**
     * Get 数据库连接池名称.
     */
    public function getDbPoolName(): ?string
    {
        return $this->dbPoolName;
    }

    /**
     * Get 主键.
     */
    public function getId(): ?array
    {
        return $this->id;
    }

    /**
     * @return Id[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Get 第一个主键.
     */
    public function getFirstId(): ?string
    {
        return $this->firstId;
    }

    /**
     * Get 字段配置.
     *
     * @return \Imi\Model\Annotation\Column[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get 字段名列表.
     *
     * @return string[]
     */
    public function getFieldNames(): array
    {
        return $this->fieldNames;
    }

    /**
     * Get 模型是否为驼峰命名.
     */
    public function isCamel(): bool
    {
        return $this->camel;
    }

    /**
     * Get 是否有关联.
     */
    public function hasRelation(): bool
    {
        return $this->relation;
    }

    /**
     * Get 类名.
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Get 自增字段名.
     */
    public function getAutoIncrementField(): ?string
    {
        return $this->autoIncrementField;
    }

    /**
     * Get 数据库字段名和 Column 注解映射.
     */
    public function getDbFields(): array
    {
        return $this->dbFields;
    }

    /**
     * Get 序列化后的所有字段属性名列表.
     *
     * @return string[]
     */
    public function getSerializableFieldNames(): array
    {
        return $this->serializableFieldNames;
    }

    /**
     * Get 是否为继承父类的模型.
     */
    public function getInherit(): bool
    {
        return $this->inherit;
    }

    /**
     * Get 真实的模型类名.
     */
    public function getRealModelClass(): string
    {
        return $this->realModelClass;
    }

    /**
     * Set 表名.
     */
    public function setTableName(?string $tableName): self
    {
        if (null === $tableName)
        {
            $this->databaseName = $this->tableName = null;
        }
        else
        {
            $list = explode('.', $tableName, 2);
            if (isset($list[1]))
            {
                $this->databaseName = $list[0];
                $this->tableName = $list[1];
            }
            else
            {
                $this->databaseName = null;
                $this->tableName = $tableName;
            }
        }

        return $this;
    }

    /**
     * Set 数据库连接池名称.
     */
    public function setDbPoolName(?string $dbPoolName): self
    {
        $this->dbPoolName = $dbPoolName;

        return $this;
    }

    /**
     * 模型对象是否作为 bean 类使用.
     */
    public function isBean(): bool
    {
        return $this->bean;
    }

    /**
     * Get 处理后的序列化字段数组.
     */
    public function getParsedSerializableFieldNames(): array
    {
        return $this->parsedSerializableFieldNames;
    }

    /**
     * 是否使用表名前缀
     */
    public function isUsePrefix(): bool
    {
        return $this->usePrefix;
    }

    /**
     * Get 是否启用增量更新.
     */
    public function isIncrUpdate(): bool
    {
        return $this->incrUpdate;
    }

    /**
     * @return \Imi\Bean\Annotation\Base[][][]
     */
    public function getPropertyAnnotations(): array
    {
        return $this->propertyAnnotations;
    }

    /**
     * @return \Imi\Bean\Annotation\Base[][]
     */
    public function getClassAnnotations(): array
    {
        return $this->classAnnotations;
    }
}
