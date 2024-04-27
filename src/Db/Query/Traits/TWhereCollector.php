<?php

declare(strict_types=1);

namespace Imi\Db\Query\Traits;

use Imi\Db\Mysql\Consts\LogicalOperator;
use Imi\Db\Query\Interfaces\IBaseWhere;
use Imi\Db\Query\Where\Where;
use Imi\Db\Query\Where\WhereBrackets;

trait TWhereCollector
{
    /**
     * {@inheritDoc}
     */
    public function whereEx(array $condition, string $logicalOperator = LogicalOperator::AND): self
    {
        if (!$condition)
        {
            return $this;
        }

        return $this->whereBrackets(fn () => $this->parseWhereEx($condition), $logicalOperator);
    }

    /**
     * {@inheritDoc}
     */
    public function whereBetween(string $fieldName, $begin, $end, string $logicalOperator = LogicalOperator::AND): self
    {
        return $this->where($fieldName, 'between', [$begin, $end], $logicalOperator);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereBetween(string $fieldName, $begin, $end): self
    {
        return $this->where($fieldName, 'between', [$begin, $end], LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function whereNotBetween(string $fieldName, $begin, $end, string $logicalOperator = LogicalOperator::AND): self
    {
        return $this->where($fieldName, 'not between', [$begin, $end], $logicalOperator);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereNotBetween(string $fieldName, $begin, $end): self
    {
        return $this->where($fieldName, 'not between', [$begin, $end], LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhere(string $fieldName, string $operation, $value): self
    {
        return $this->where($fieldName, $operation, $value, LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereRaw(string $where, array $binds = []): self
    {
        return $this->whereRaw($where, LogicalOperator::OR, $binds);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereBrackets(callable $callback): self
    {
        return $this->whereBrackets($callback, LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereStruct(IBaseWhere $where): self
    {
        return $this->whereStruct($where, LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereEx(array $condition): self
    {
        return $this->whereEx($condition, LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function whereIn(string $fieldName, array $list, string $logicalOperator = LogicalOperator::AND): self
    {
        return $this->where($fieldName, 'in', $list, $logicalOperator);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereIn(string $fieldName, array $list): self
    {
        return $this->where($fieldName, 'in', $list, LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function whereNotIn(string $fieldName, array $list, string $logicalOperator = LogicalOperator::AND): self
    {
        return $this->where($fieldName, 'not in', $list, $logicalOperator);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereNotIn(string $fieldName, array $list): self
    {
        return $this->where($fieldName, 'not in', $list, LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereIsNull(string $fieldName): self
    {
        return $this->whereIsNull($fieldName, LogicalOperator::OR);
    }

    /**
     * {@inheritDoc}
     */
    public function orWhereIsNotNull(string $fieldName): self
    {
        return $this->whereIsNotNull($fieldName, LogicalOperator::OR);
    }

    protected function parseWhereEx(array $condition): array
    {
        $result = [];
        foreach ($condition as $key => $value)
        {
            if (null === LogicalOperator::getText(strtolower($key)))
            {
                // 条件 k => v
                if (\is_array($value))
                {
                    $operator = strtolower($value[0] ?? '');
                    switch ($operator)
                    {
                        case 'between':
                            if (!isset($value[2]))
                            {
                                throw new \RuntimeException('Between must have 3 params');
                            }
                            $result[] = new Where($key, 'between', [$value[1], $value[2]]);
                            break;
                        case 'not between':
                            if (!isset($value[2]))
                            {
                                throw new \RuntimeException('Not between must have 3 params');
                            }
                            $result[] = new Where($key, 'not between', [$value[1], $value[2]]);
                            break;
                        case 'in':
                            if (!isset($value[1]))
                            {
                                throw new \RuntimeException('In must have 3 params');
                            }
                            $result[] = new Where($key, 'in', $value[1]);
                            break;
                        case 'not in':
                            if (!isset($value[1]))
                            {
                                throw new \RuntimeException('Not in must have 3 params');
                            }
                            $result[] = new Where($key, 'not in', $value[1]);
                            break;
                        default:
                            $result[] = new Where($key, $operator, $value[1]);
                            break;
                    }
                }
                else
                {
                    $result[] = new Where($key, '=', $value);
                }
            }
            else
            {
                // 逻辑运算符
                $result[] = new WhereBrackets(fn () => $this->parseWhereEx($value), $key);
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function orPostWhere(callable $callback): self
    {
        return $this->postWhere($callback, LogicalOperator::OR);
    }
}
