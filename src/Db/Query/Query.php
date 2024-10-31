<?php

declare(strict_types=1);

namespace Imi\Db\Query;

use Imi\Bean\BeanFactory;
use Imi\Db\Db;
use Imi\Db\Interfaces\IDb;
use Imi\Db\Mysql\Consts\LogicalOperator;
use Imi\Db\Query\Having\Having;
use Imi\Db\Query\Having\HavingBrackets;
use Imi\Db\Query\Interfaces\IBaseWhere;
use Imi\Db\Query\Interfaces\IFullTextOptions;
use Imi\Db\Query\Interfaces\IHaving;
use Imi\Db\Query\Interfaces\IPaginateResult;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Db\Query\Interfaces\IResult;
use Imi\Db\Query\Result\ChunkByOffsetResult;
use Imi\Db\Query\Result\ChunkResult;
use Imi\Db\Query\Result\CursorResult;
use Imi\Db\Query\Traits\TWhereCollector;
use Imi\Db\Query\Where\Where;
use Imi\Db\Query\Where\WhereBrackets;
use Imi\Db\Query\Where\WhereFullText;
use Imi\Model\Model;
use Imi\Util\Pagination;

abstract class Query implements IQuery
{
    use TWhereCollector;

    public const SELECT_BUILDER_CLASS = '';

    public const INSERT_BUILDER_CLASS = '';

    public const BATCH_INSERT_BUILDER_CLASS = '';

    public const UPDATE_BUILDER_CLASS = '';

    public const REPLACE_BUILDER_CLASS = '';

    public const DELETE_BUILDER_CLASS = '';

    public const FULL_TEXT_OPTIONS_CLASS = '';

    /**
     * 操作记录.
     */
    protected ?QueryOption $option = null;

    /**
     * 数据绑定.
     */
    protected array $binds = [];

    /**
     * 数据库操作对象
     */
    protected ?IDb $db = null;

    /**
     * 连接池名称.
     */
    protected ?string $poolName = null;

    /**
     * 查询结果类的类名，为null则为数组.
     *
     * @var class-string<Model>|null
     */
    protected ?string $modelClass = null;

    /**
     * 查询类型.
     */
    protected ?int $queryType = null;

    /**
     * 是否初始化时候就设定了查询类型.
     */
    protected bool $isInitQueryType = false;

    /**
     * 是否初始化时候就设定了连接.
     */
    protected bool $isInitDb = false;

    /**
     * 数据库字段自增.
     */
    protected int $dbParamInc = 0;

    protected string $originPrefix = '';

    /**
     * 查询器别名集合.
     *
     * @var static[]
     */
    protected static array $aliasMap = [];

    /**
     * 当前别名.
     */
    protected ?string $alias = null;

    /**
     * 查询结果集类名.
     *
     * @var class-string<IResult>
     */
    protected string $resultClass = Result::class;

    /**
     * 别名 Sql 数据集合.
     */
    protected static array $aliasSqlMap = [];

    /**
     * 构建 Sql 前的回调.
     *
     * @var callable[]
     */
    protected array $beforeBuildSqlCallbacks = [];

    public function __construct(?IDb $db = null, ?string $modelClass = null, ?string $poolName = null, ?int $queryType = null, ?string $prefix = null)
    {
        $this->db = $db;
        $this->isInitDb = (bool) $db;
        $this->poolName = $poolName;
        $this->modelClass = $modelClass;
        $this->queryType = $queryType ?? QueryType::WRITE;
        $this->isInitQueryType = null !== $queryType;
        if (null === $prefix)
        {
            if ($db = $this->db)
            {
                $this->originPrefix = $db->getOption()['prefix'] ?? '';
            }
            else
            {
                $this->originPrefix = Db::getInstanceConfig($this->poolName, $this->queryType)['prefix'] ?? '';
            }
        }
        else
        {
            $this->originPrefix = $prefix;
        }
        $this->initQuery();
    }

    protected function initQuery(): void
    {
        $this->dbParamInc = 0;
        if (!$this->isInitQueryType)
        {
            $this->queryType = QueryType::WRITE;
        }
        $this->option = new QueryOption($this->originPrefix);
    }

    public function __clone()
    {
        if (!$this->isInitDb)
        {
            $this->db = null;
        }
        $this->option = clone $this->option;
    }

    public static function newInstance(?IDb $db = null, ?string $modelClass = null, ?string $poolName = null, ?int $queryType = null): self
    {
        return BeanFactory::newInstance(static::class, $db, $modelClass, $poolName, $queryType);
    }

    /**
     * {@inheritDoc}
     */
    public function getOption(): QueryOption
    {
        return $this->option;
    }

    /**
     * {@inheritDoc}
     */
    public function setOption($option): self
    {
        $this->dbParamInc = 0;
        $this->option = $option;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDb(): IDb
    {
        if (!$this->isInitDb)
        {
            $this->db = Db::getInstance($this->poolName, $this->queryType);
        }

        return $this->db;
    }

    /**
     * 设置表前缀
     */
    public function tablePrefix(string $prefix): self
    {
        $this->option->table->setPrefix($prefix);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function table(string $table, ?string $alias = null, ?string $database = null): self
    {
        $optionTable = $this->option->table;
        $optionTable->useRaw(false);
        $optionTable->setTable($table);
        $optionTable->setAlias($alias);
        $optionTable->setDatabase($database);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function tableRaw(string $raw, ?string $alias = null): self
    {
        $optionTable = $this->option->table;
        $optionTable->useRaw(true);
        $optionTable->setRawSQL($raw);
        $optionTable->setAlias($alias);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function from(string $table, ?string $alias = null, ?string $database = null): self
    {
        return $this->table($table, $alias, $database);
    }

    /**
     * {@inheritDoc}
     */
    public function fromRaw(string $raw): self
    {
        return $this->tableRaw($raw);
    }

    /**
     * 设置分区列表.
     */
    public function partition(?array $partitions = null): self
    {
        $partition = ($this->option->partition ??= new Partition());
        $partition->useRaw(false);
        $partition->setPartitions($partitions);

        return $this;
    }

    /**
     * 设置分区原生 SQL.
     */
    public function partitionRaw(string $partitionSql): self
    {
        $partition = ($this->option->partition ??= new Partition());
        $partition->useRaw(true);
        $partition->setRawSQL($partitionSql);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function distinct(bool $isDistinct = true): self
    {
        $this->option->distinct = $isDistinct;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function field(...$fields): self
    {
        $option = $this->option;
        if (!isset($fields[1]) && \is_array($fields[0]))
        {
            $option->field = array_merge($option->field, $fields[0]);
        }
        else
        {
            $option->field = array_merge($option->field, $fields);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function fieldRaw(string $raw, ?string $alias = null, array $binds = []): self
    {
        $field = new Field();
        $field->useRaw();
        $field->setRawSQL($raw, $binds);
        if (null !== $alias)
        {
            $field->setAlias($alias);
        }
        $this->option->field[] = $field;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function where(string $fieldName, string $operation, $value, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->option->where[] = new Where($fieldName, $operation, $value, $logicalOperator);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereRaw(string $raw, string $logicalOperator = LogicalOperator::AND, array $binds = []): self
    {
        $where = new Where();
        $where->useRaw();
        $where->setRawSQL($raw, $binds);
        $where->setLogicalOperator($logicalOperator);
        $this->option->where[] = $where;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereBrackets(callable $callback, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->option->where[] = new WhereBrackets($callback, $logicalOperator);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereStruct(IBaseWhere $where, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->option->where[] = $where;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereIsNull(string $fieldName, string $logicalOperator = LogicalOperator::AND): self
    {
        return $this->whereRaw($this->fieldQuote($fieldName) . ' is null', $logicalOperator);
    }

    /**
     * {@inheritDoc}
     */
    public function whereIsNotNull(string $fieldName, string $logicalOperator = LogicalOperator::AND): self
    {
        return $this->whereRaw($this->fieldQuote($fieldName) . ' is not null', $logicalOperator);
    }

    /**
     * {@inheritDoc}
     */
    public function postWhere(callable $callback, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->option->postWhere[] = new WhereBrackets($callback, $logicalOperator);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function join(string $table, string $left, string $operation, string $right, ?string $tableAlias = null, IBaseWhere $where = null, string $type = 'inner'): self
    {
        $this->option->join[] = new Join($this, $table, $left, $operation, $right, $tableAlias, $where, $type);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function joinRaw(string $raw, array $binds = []): self
    {
        $join = new Join($this);
        $join->useRaw();
        $join->setRawSQL($raw, $binds);
        $this->option->join[] = $join;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function leftJoin(string $table, string $left, string $operation, string $right, ?string $tableAlias = null, IBaseWhere $where = null): self
    {
        return $this->join($table, $left, $operation, $right, $tableAlias, $where, 'left');
    }

    /**
     * {@inheritDoc}
     */
    public function rightJoin(string $table, string $left, string $operation, string $right, ?string $tableAlias = null, IBaseWhere $where = null): self
    {
        return $this->join($table, $left, $operation, $right, $tableAlias, $where, 'right');
    }

    /**
     * {@inheritDoc}
     */
    public function crossJoin(string $table, string $left, string $operation, string $right, ?string $tableAlias = null, IBaseWhere $where = null): self
    {
        return $this->join($table, $left, $operation, $right, $tableAlias, $where, 'cross');
    }

    /**
     * {@inheritDoc}
     */
    public function order(string $field, string $direction = 'asc'): self
    {
        $this->option->order[] = new Order($field, $direction);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function orderRaw($raw, array $binds = []): self
    {
        $optionOrder = &$this->option->order;
        if (\is_array($raw))
        {
            foreach ($raw as $k => $v)
            {
                if (\is_int($k))
                {
                    $fieldName = $v;
                    $direction = 'asc';
                }
                else
                {
                    $fieldName = $k;
                    $direction = $v;
                }
                $optionOrder[] = new Order($fieldName, $direction);
            }
        }
        else
        {
            $order = new Order();
            $order->useRaw();
            $order->setRawSQL($raw, $binds);
            $optionOrder[] = $order;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function page(?int $page, ?int $count): self
    {
        $pagination = new Pagination($page, $count);
        $option = $this->option;
        $option->offset = $pagination->getLimitOffset();
        $option->limit = $count;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function offset(?int $offset): self
    {
        $this->option->offset = $offset;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function limit(?int $limit): self
    {
        $this->option->limit = $limit;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function group(string ...$groups): self
    {
        $optionGroup = &$this->option->group;
        foreach ($groups as $item)
        {
            $group = new Group();
            $group->setValue($item, $this);
            $optionGroup[] = $group;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function groupRaw(string $raw, array $binds = []): self
    {
        $group = new Group();
        $group->useRaw();
        $group->setRawSQL($raw, $binds);
        $this->option->group[] = $group;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function having(string $fieldName, string $operation, $value, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->option->having[] = new Having($fieldName, $operation, $value, $logicalOperator);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function havingRaw(string $raw, string $logicalOperator = LogicalOperator::AND, array $binds = []): self
    {
        $having = new Having();
        $having->useRaw();
        $having->setRawSQL($raw, $binds);
        $having->setLogicalOperator($logicalOperator);
        $this->option->having[] = $having;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function havingBrackets(callable $callback, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->option->having[] = new HavingBrackets($callback, $logicalOperator);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function havingStruct(IHaving $having, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->option->having[] = $having;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function bindValue($name, $value, int $dataType = \PDO::PARAM_STR): self
    {
        $this->binds[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function bindValues(array $values): self
    {
        $binds = &$this->binds;
        foreach ($values as $k => $v)
        {
            $binds[$k] = $v;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBinds(): array
    {
        return $this->binds;
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $page, int $count, array $options = []): IPaginateResult
    {
        if ($options['total'] ?? true)
        {
            $query = (clone $this);
            $option = $query->option;
            $option->order = [];
            if (isset($options['countField']))
            {
                $field = new Field();
                $field->useRaw();
                $field->setRawSQL($options['countField']);
                $option->field = [
                    new WrapField('count(', [$field], ')'),
                ];
                $total = (int) $query->select()->getScalar();
            }
            elseif ($option->distinct)
            {
                $option->field = [
                    new WrapField('count(distinct ', $option->field ?: ['*'], ')'),
                ];
                $total = (int) $query->select()->getScalar();
            }
            else
            {
                $total = (int) $query->count();
            }
        }
        else
        {
            $total = null;
        }
        $pagination = new Pagination($page, $count);

        return new PaginateResult($this->page($page, $count)->select(), $pagination->getLimitOffset(), $count, $total, null === $total ? null : $pagination->calcPageCount($total), $options);
    }

    /**
     * {@inheritDoc}
     */
    public function count(string $field = '*'): int
    {
        return (int) $this->aggregate('count', $field);
    }

    /**
     * {@inheritDoc}
     */
    public function sum(string $field)
    {
        return $this->aggregate('sum', $field);
    }

    /**
     * {@inheritDoc}
     */
    public function avg(string $field)
    {
        return $this->aggregate('avg', $field);
    }

    /**
     * {@inheritDoc}
     */
    public function max(string $field)
    {
        return $this->aggregate('max', $field);
    }

    /**
     * {@inheritDoc}
     */
    public function min(string $field)
    {
        return $this->aggregate('min', $field);
    }

    /**
     * {@inheritDoc}
     */
    public function aggregate(string $functionName, string $fieldName)
    {
        $field = new Field();
        $field->useRaw();
        $field->setRawSQL($functionName . '(' . $this->fieldQuote($fieldName) . ')');
        $this->option->field = [
            $field,
        ];

        return $this->select()->getScalar();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(string $sql): IResult
    {
        return $this->executeEx($sql, $this->resultClass);
    }

    /**
     * @template T
     *
     * @param class-string<T> $resultClass
     *
     * @return T
     */
    protected function executeEx(string $sql, string $resultClass)
    {
        try
        {
            $db = $this->getDb();
            $stmt = $db->prepare($sql);
            $binds = $this->binds;
            $this->binds = [];
            $stmt->execute($binds);

            return new $resultClass($stmt, $this->modelClass, true);
        }
        finally
        {
            $this->initQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAutoParamName(string $prefix = ':p'): string
    {
        return $prefix . dechex(++$this->dbParamInc);
    }

    /**
     * {@inheritDoc}
     */
    public function setData($data): self
    {
        $this->option->saveData = $data;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setField(string $fieldName, $value): self
    {
        $this->option->saveData[$fieldName] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFieldExp(string $fieldName, string $exp, array $binds = []): self
    {
        $this->option->saveData[$fieldName] = new Raw($exp, $binds);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFieldInc(string $fieldName, float $incValue = 1): self
    {
        $name = $this->getAutoParamName(':fip');

        return $this->setFieldExp($fieldName, $this->fieldQuote($fieldName) . ' + ' . $name, [$name => $incValue]);
    }

    /**
     * {@inheritDoc}
     */
    public function setFieldDec(string $fieldName, float $decValue = 1): self
    {
        $name = $this->getAutoParamName(':fdp');

        return $this->setFieldExp($fieldName, $this->fieldQuote($fieldName) . ' - ' . $name, [$name => $decValue]);
    }

    /**
     * {@inheritDoc}
     */
    protected function isInTransaction(): bool
    {
        if (QueryType::WRITE !== $this->queryType)
        {
            return false;
        }

        return $this->getDb()->inTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function alias(string $name, $callable = null): self
    {
        $aliasMap = &static::$aliasMap;
        if (!isset($aliasMap[$name]))
        {
            if ($callable)
            {
                $callable($this);
            }
            $this->alias = $name;
            $aliasMap[$name] = $this;
        }

        return clone $aliasMap[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function lock($value): self
    {
        $this->option->lock = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setResultClass(string $resultClass)
    {
        $this->resultClass = $resultClass;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getResultClass(): string
    {
        return $this->resultClass;
    }

    /**
     * {@inheritDoc}
     */
    public function buildSelectSql(): string
    {
        $this->dbParamInc = 0;
        if ($this->beforeBuildSqlCallbacks)
        {
            foreach ($this->beforeBuildSqlCallbacks as $callback)
            {
                $callback($this);
            }
            $this->beforeBuildSqlCallbacks = [];
        }

        $alias = $this->alias;
        $aliasSqlMap = &static::$aliasSqlMap;
        if ($alias && isset($aliasSqlMap[$alias]))
        {
            $aliasSqlData = $aliasSqlMap[$alias];
            $sql = $aliasSqlData['sql'];
            $binds = $aliasSqlData['binds'];
            if ($binds)
            {
                if ($this->binds)
                {
                    $this->binds = array_merge($binds, $this->binds);
                }
                else
                {
                    $this->binds = $binds;
                }
            }
        }
        else
        {
            if ($alias)
            {
                $binds = $this->binds;
                $this->binds = [];
            }
            $builderClass = static::SELECT_BUILDER_CLASS;
            $sql = (new $builderClass($this))->build();
            if ($alias)
            {
                // @phpstan-ignore-next-line
                $originBinds = $binds;
                $binds = $this->binds;
                if ($binds)
                {
                    $this->binds = array_merge($originBinds, $binds);
                }
                else
                {
                    $this->binds = $originBinds;
                }
                $aliasSqlMap[$alias] = [
                    'sql'   => $sql,
                    'binds' => $binds,
                ];
            }
        }
        if (!$this->isInitQueryType && !$this->isInTransaction())
        {
            $this->queryType = QueryType::READ;
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function buildInsertSql($data = null): string
    {
        $this->dbParamInc = 0;
        if ($this->beforeBuildSqlCallbacks)
        {
            foreach ($this->beforeBuildSqlCallbacks as $callback)
            {
                $callback($this);
            }
            $this->beforeBuildSqlCallbacks = [];
        }
        $alias = $this->alias;
        $aliasSqlMap = &static::$aliasSqlMap;
        if ($alias && isset($aliasSqlMap[$alias]))
        {
            $aliasSqlData = $aliasSqlMap[$alias];
            $sql = $aliasSqlData['sql'];
            $binds = $aliasSqlData['binds'];
            if ($binds)
            {
                if ($this->binds)
                {
                    $this->binds = array_merge($binds, $this->binds);
                }
                else
                {
                    $this->binds = $binds;
                }
            }
            $bindValues = [];
            $numberKey = isset($data[0]);
            foreach ($data as $k => $v)
            {
                if ($numberKey)
                {
                    $bindValues[':' . ($k + 1)] = $v;
                }
                else
                {
                    $bindValues[':' . $k] = $v;
                }
            }
            $this->bindValues($bindValues);
        }
        else
        {
            if ($alias)
            {
                $binds = $this->binds;
                $this->binds = [];
            }
            $builderClass = static::INSERT_BUILDER_CLASS;
            $sql = (new $builderClass($this))->build($data);
            if ($alias)
            {
                $aliasSqlMap[$alias] = [
                    'sql'   => $sql,
                    // @phpstan-ignore-next-line
                    'binds' => $binds,
                ];
            }
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function buildBatchInsertSql($data = null): string
    {
        $this->dbParamInc = 0;
        if ($this->beforeBuildSqlCallbacks)
        {
            foreach ($this->beforeBuildSqlCallbacks as $callback)
            {
                $callback($this);
            }
            $this->beforeBuildSqlCallbacks = [];
        }
        $builderClass = static::BATCH_INSERT_BUILDER_CLASS;

        return (new $builderClass($this))->build($data);
    }

    /**
     * {@inheritDoc}
     */
    public function buildUpdateSql($data = null): string
    {
        $this->dbParamInc = 0;
        if ($this->beforeBuildSqlCallbacks)
        {
            foreach ($this->beforeBuildSqlCallbacks as $callback)
            {
                $callback($this);
            }
            $this->beforeBuildSqlCallbacks = [];
        }
        $alias = $this->alias;
        $aliasSqlMap = &static::$aliasSqlMap;
        if ($alias && isset($aliasSqlMap[$alias]))
        {
            $aliasSqlData = $aliasSqlMap[$alias];
            $sql = $aliasSqlData['sql'];
            $binds = $aliasSqlData['binds'];
            if ($binds)
            {
                if ($this->binds)
                {
                    $this->binds = array_merge($binds, $this->binds);
                }
                else
                {
                    $this->binds = $binds;
                }
            }
            $bindValues = [];
            foreach ($data as $k => $v)
            {
                $bindValues[':' . $k] = $v;
            }
            $this->bindValues($bindValues);
        }
        else
        {
            if ($alias)
            {
                $binds = $this->binds;
                $this->binds = [];
            }
            $builderClass = static::UPDATE_BUILDER_CLASS;
            $sql = (new $builderClass($this))->build($data);
            if ($alias)
            {
                // @phpstan-ignore-next-line
                $originBinds = $binds;
                $binds = $this->binds;
                if ($binds)
                {
                    $this->binds = array_merge($originBinds, $binds);
                }
                else
                {
                    $this->binds = $originBinds;
                }
                $aliasSqlMap[$alias] = [
                    'sql'   => $sql,
                    'binds' => $binds,
                ];
            }
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function buildReplaceSql($data = null, array $uniqueFields = []): string
    {
        $this->dbParamInc = 0;
        if ($this->beforeBuildSqlCallbacks)
        {
            foreach ($this->beforeBuildSqlCallbacks as $callback)
            {
                $callback($this);
            }
            $this->beforeBuildSqlCallbacks = [];
        }
        $alias = $this->alias;
        $aliasSqlMap = &static::$aliasSqlMap;
        if ($alias && isset($aliasSqlMap[$alias]))
        {
            $aliasSqlData = $aliasSqlMap[$alias];
            $sql = $aliasSqlData['sql'];
            $binds = $aliasSqlData['binds'];
            if ($binds)
            {
                if ($this->binds)
                {
                    $this->binds = array_merge($binds, $this->binds);
                }
                else
                {
                    $this->binds = $binds;
                }
            }
            $bindValues = [];
            foreach ($data as $k => $v)
            {
                $bindValues[':' . $k] = $v;
            }
            $this->bindValues($bindValues);
        }
        else
        {
            if ($alias)
            {
                $binds = $this->binds;
                $this->binds = [];
            }
            $builderClass = static::REPLACE_BUILDER_CLASS;
            $sql = (new $builderClass($this))->build($data, $uniqueFields);
            if ($alias)
            {
                // @phpstan-ignore-next-line
                $originBinds = $binds;
                $binds = $this->binds;
                if ($binds)
                {
                    $this->binds = array_merge($originBinds, $binds);
                }
                else
                {
                    $this->binds = $originBinds;
                }
                $aliasSqlMap[$alias] = [
                    'sql'   => $sql,
                    'binds' => $binds,
                ];
            }
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function buildDeleteSql(): string
    {
        $this->dbParamInc = 0;
        if ($this->beforeBuildSqlCallbacks)
        {
            foreach ($this->beforeBuildSqlCallbacks as $callback)
            {
                $callback($this);
            }
            $this->beforeBuildSqlCallbacks = [];
        }
        $alias = $this->alias;
        $aliasSqlMap = &static::$aliasSqlMap;
        if ($alias && isset($aliasSqlMap[$alias]))
        {
            $aliasSqlData = $aliasSqlMap[$alias];
            $sql = $aliasSqlData['sql'];
            $binds = $aliasSqlData['binds'];
            if ($binds)
            {
                if ($this->binds)
                {
                    $this->binds = array_merge($binds, $this->binds);
                }
                else
                {
                    $this->binds = $binds;
                }
            }
        }
        else
        {
            if ($alias)
            {
                $binds = $this->binds;
                $this->binds = [];
            }
            $builderClass = static::DELETE_BUILDER_CLASS;
            $sql = (new $builderClass($this))->build();
            if ($alias)
            {
                // @phpstan-ignore-next-line
                $originBinds = $binds;
                $binds = $this->binds;
                if ($binds)
                {
                    $this->binds = array_merge($originBinds, $binds);
                }
                else
                {
                    $this->binds = $originBinds;
                }
                $aliasSqlMap[$alias] = [
                    'sql'   => $sql,
                    'binds' => $binds,
                ];
            }
        }

        return $sql;
    }

    /**
     * {@inheritDoc}
     */
    public function select(): IResult
    {
        return $this->execute($this->buildSelectSql());
    }

    /**
     * {@inheritDoc}
     */
    public function find(?string $className = null)
    {
        return $this->limit(1)
            ->select()
            ->get($className);
    }

    /**
     * {@inheritDoc}
     */
    public function value(string $field, $default = null)
    {
        $result = $this
            ->limit(1)
            ->field($field)
            ->select();

        return $result->getScalar($field) ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function column($fields, ?string $key = null): array
    {
        $fields = (array) $fields;
        $fields = array_unique($fields);
        $rawFields = $fields;

        if (empty($key))
        {
            $key = null;
        }
        if ($key && !\in_array($key, $fields))
        {
            $fields[] = $key;
        }

        $result = $this
            ->field(...$fields)
            ->select();

        $records = $result->getStatementRecords();
        if (1 === \count($rawFields))
        {
            $column = $rawFields[0];
            if (strpos($column, '.'))
            {
                [, $column] = explode('.', $column);
            }

            return array_column($records, $column, $key);
        }
        else
        {
            return array_column($records, null, $key);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function cursor(): CursorResult
    {
        return $this->executeEx($this->buildSelectSql(), CursorResult::class);
    }

    /**
     * {@inheritDoc}
     */
    public function chunkById(int $count, string $column, ?string $alias = null, string $orderBy = 'asc'): ChunkResult
    {
        $alias ??= $column;

        $this->option->order = [];

        return new ChunkResult($this, $count, $column, $alias, $orderBy);
    }

    /**
     * {@inheritDoc}
     */
    public function chunkByOffset(int $count): ChunkByOffsetResult
    {
        return new ChunkByOffsetResult($this, $count);
    }

    /**
     * 查询所有记录，返回分块迭代器.
     *
     * @return mixed
     */
    public function chunkEach(int $count, string $column, ?string $alias = null)
    {
        return $this->chunkById($count, $column, $alias)->each();
    }

    /**
     * {@inheritDoc}
     */
    public function insert($data = null): IResult
    {
        return $this->execute($this->buildInsertSql($data));
    }

    /**
     * {@inheritDoc}
     */
    public function batchInsert($data = null): IResult
    {
        return $this->execute($this->buildBatchInsertSql($data));
    }

    /**
     * {@inheritDoc}
     */
    public function update($data = null): IResult
    {
        return $this->execute($this->buildUpdateSql($data));
    }

    /**
     * {@inheritDoc}
     */
    public function replace($data = null, array $uniqueFields = []): IResult
    {
        return $this->execute($this->buildReplaceSql($data, $uniqueFields));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(): IResult
    {
        return $this->execute($this->buildDeleteSql());
    }

    /**
     * {@inheritDoc}
     */
    public function fullText($fieldNames, string $searchText, ?IFullTextOptions $options = null): self
    {
        if (!$options)
        {
            $class = static::FULL_TEXT_OPTIONS_CLASS;
            /** @var IFullTextOptions $options */
            $options = new $class();
        }

        $options->setFieldNames($fieldNames)
                ->setSearchText($searchText);

        // where
        $whereLogicalOperator = $options->getWhereLogicalOperator();
        if (null !== $whereLogicalOperator)
        {
            $this->option->where[] = new WhereFullText($options, $whereLogicalOperator);
        }

        // score field
        $scoreFieldName = $options->getScoreFieldName();
        if (null !== $scoreFieldName)
        {
            $this->beforeBuildSqlCallbacks[] = fn () => $this->fieldRaw($options->toScoreSql($this), '' === $scoreFieldName ? null : $scoreFieldName);
        }

        // order score
        $orderDirection = $options->getOrderDirection();
        if (null !== $orderDirection)
        {
            $this->beforeBuildSqlCallbacks[] = function () use ($scoreFieldName, $options, $orderDirection) {
                if (null === $scoreFieldName || '' === $scoreFieldName)
                {
                    $this->orderRaw('(' . $options->toScoreSql($this) . ') ' . $orderDirection);
                }
                else
                {
                    $this->order($scoreFieldName, $orderDirection);
                }
            };
        }

        return $this;
    }
}
