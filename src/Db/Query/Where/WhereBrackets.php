<?php

declare(strict_types=1);

namespace Imi\Db\Query\Where;

use Imi\Db\Mysql\Consts\LogicalOperator;
use Imi\Db\Query\Interfaces\IBaseWhere;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Db\Query\Interfaces\IWhereBrackets;
use Imi\Db\Query\Interfaces\IWhereCollector;
use Imi\Db\Query\Traits\TRaw;
use Imi\Db\Query\WhereCollector;

class WhereBrackets extends BaseWhere implements IWhereBrackets
{
    use TRaw;

    /**
     * 回调.
     *
     * @var callable
     */
    protected $callback;

    public function __construct(callable $callback = null, string $logicalOperator = LogicalOperator::AND)
    {
        $this->callback = $callback;
        $this->logicalOperator = $logicalOperator;
    }

    /**
     * {@inheritDoc}
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }

    /**
     * {@inheritDoc}
     */
    public function setCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function toStringWithoutLogic(IQuery $query): string
    {
        if ($this->isRaw)
        {
            return $this->rawSQL;
        }
        $binds = &$this->binds;
        $whereCollector = new WhereCollector($query);
        $callResult = ($this->callback)($query, $whereCollector);
        if (\is_array($callResult))
        {
            if (empty($callResult))
            {
                return '';
            }

            $result = '(';
            foreach ($callResult as $i => $callResultItem)
            {
                if ($callResultItem instanceof IBaseWhere)
                {
                    if (0 === $i)
                    {
                        $result .= $callResultItem->toStringWithoutLogic($query);
                    }
                    else
                    {
                        $result .= ' ' . $callResultItem->getLogicalOperator() . ' ' . $callResultItem->toStringWithoutLogic($query);
                    }
                    $binds = array_merge($binds, $callResultItem->getBinds());
                }
                else
                {
                    if ($i > 0)
                    {
                        $result .= ' ';
                    }
                    $result .= $callResultItem;
                }
            }

            return $result . ')';
        }
        elseif ($callResult instanceof IBaseWhere)
        {
            $result = $callResult->toStringWithoutLogic($query);
            $binds = array_merge($binds, $callResult->getBinds());

            return '(' . $result . ')';
        }
        elseif (null === $callResult || $callResult instanceof IWhereCollector)
        {
            $result = '(';
            foreach ([
                $whereCollector->getWhere(),
                $whereCollector->getPostWhere(),
            ] as $wheres)
            {
                foreach ($wheres as $i => $where)
                {
                    if (0 === $i)
                    {
                        $result .= $where->toStringWithoutLogic($query);
                    }
                    else
                    {
                        $result .= ' ' . $where->getLogicalOperator() . ' ' . $where->toStringWithoutLogic($query);
                    }
                    $binds = array_merge($binds, $where->getBinds());
                }
            }

            return $result . ')';
        }
        else
        {
            return '(' . $callResult . ')';
        }
    }
}
