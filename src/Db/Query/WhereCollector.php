<?php

declare(strict_types=1);

namespace Imi\Db\Query;

use Imi\Db\Mysql\Consts\LogicalOperator;
use Imi\Db\Query\Interfaces\IBaseWhere;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Db\Query\Interfaces\IWhereCollector;
use Imi\Db\Query\Traits\TWhereCollector;
use Imi\Db\Query\Where\Where;
use Imi\Db\Query\Where\WhereBrackets;

class WhereCollector implements IWhereCollector
{
    use TWhereCollector;

    /**
     * @var IBaseWhere[]
     */
    protected array $where = [];

    /**
     * @var IBaseWhere[]
     */
    protected array $postWhere = [];

    protected IQuery $query;

    public function __construct(IQuery $query)
    {
        $this->query = $query;
    }

    /**
     * @return IBaseWhere[]
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * @return IBaseWhere[]
     */
    public function getPostWhere(): array
    {
        return $this->postWhere;
    }

    /**
     * {@inheritDoc}
     */
    public function where(string $fieldName, string $operation, $value, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->where[] = new Where($fieldName, $operation, $value, $logicalOperator);

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
        $this->where[] = $where;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereBrackets(callable $callback, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->where[] = new WhereBrackets($callback, $logicalOperator);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereStruct(IBaseWhere $where, string $logicalOperator = LogicalOperator::AND): self
    {
        $this->where[] = $where;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereIsNull(string $fieldName, string $logicalOperator = LogicalOperator::AND): self
    {
        return $this->whereRaw($this->query->fieldQuote($fieldName) . ' is null', $logicalOperator);
    }

    /**
     * {@inheritDoc}
     */
    public function whereIsNotNull(string $fieldName, string $logicalOperator = LogicalOperator::AND): self
    {
        return $this->whereRaw($this->query->fieldQuote($fieldName) . ' is not null', $logicalOperator);
    }

    /**
     * @return static
     */
    public function postWhere(callable $callback, string $logicalOperator = 'and'): self
    {
        $this->postWhere[] = new WhereBrackets($callback, $logicalOperator);

        return $this;
    }
}
