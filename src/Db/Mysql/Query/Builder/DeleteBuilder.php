<?php

declare(strict_types=1);

namespace Imi\Db\Mysql\Query\Builder;

use Imi\Db\Query\QueryOption;

class DeleteBuilder extends BaseBuilder
{
    public function build(...$args): string
    {
        parent::build(...$args);
        $query = $this->query;
        /** @var QueryOption $option */
        $option = $query->getOption();

        $sql = 'delete from ' . $option->table->toString($query)
                . (($option->partition && '' !== ($partition = $option->partition->toString($query))) ? (' PARTITION(' . $partition . ')') : '')
                . $this->parseWhere($option->where, $option->postWhere)
                . $this->parseOrder($option->order)
                . $this->parseLimit($option->offset, $option->limit)
        ;
        $query->bindValues($this->params);

        return $sql;
    }
}
