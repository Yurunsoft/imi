<?php

declare(strict_types=1);

namespace Imi\Pgsql\Db\Query\Builder;

use Imi\Db\Query\QueryOption;

class SelectBuilder extends BaseBuilder
{
    public function build(...$args): string
    {
        parent::build(...$args);
        $query = $this->query;
        /** @var QueryOption $option */
        $option = $query->getOption();
        $sql = 'select ' . $this->parseDistinct($option->distinct)
                . $this->parseField($option->field)
                . ' from '
                . $option->table->toString($query)
                . $this->parseJoin($option->join)
                . $this->parseWhere($option->where, $option->postWhere)
                . $this->parseGroup($option->group)
                . $this->parseHaving($option->having)
                . $this->parseOrder($option->order)
                . $this->parseLimit($option->offset, $option->limit)
        ;
        $query->bindValues($this->params);

        return $sql;
    }
}
