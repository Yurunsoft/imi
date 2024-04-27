<?php

declare(strict_types=1);

namespace Imi\Db\Mysql\Query\Builder;

use Imi\Db\Query\QueryOption;

class UpdateBuilder extends BaseBuilder
{
    public function build(...$args): string
    {
        parent::build(...$args);
        $query = $this->query;
        $params = &$this->params;
        /** @var QueryOption $option */
        $option = $query->getOption();
        [$data] = $args;
        if (null === $data)
        {
            $data = $option->saveData;
        }

        // set后面的field=value
        $setStrs = [];
        $jsonSets = [];
        foreach ($data as $k => $v)
        {
            if ($v instanceof \Imi\Db\Query\Raw)
            {
                if (\is_int($k))
                {
                    $setStrs[] = $v->toString($query);
                }
                else
                {
                    $matches = $query->parseKeywordText($k);
                    $field = $query->parseKeywordToText($matches['keywords']);
                    if ($matches['jsonKeywords'])
                    {
                        $jsonSets[$field][] = [
                            'jsonKeywords' => $matches['jsonKeywords'],
                            'raw'          => $v->toString($query),
                        ];
                    }
                    else
                    {
                        $setStrs[] = $field . ' = ' . $v->toString($query);
                    }
                }
                $binds = $v->getBinds();
                if ($binds)
                {
                    $params = array_merge($params, $binds);
                }
            }
            else
            {
                $matches = $query->parseKeywordText($k);
                $field = $query->parseKeywordToText($matches['keywords']);
                if ($matches['jsonKeywords'])
                {
                    if (\is_scalar($v))
                    {
                        $valueParam = $query->getAutoParamName();
                        $jsonSets[$field][] = [
                            'jsonKeywords' => $matches['jsonKeywords'],
                            'valueParam'   => $valueParam,
                        ];
                        $params[$valueParam] = $v;
                    }
                    else
                    {
                        $jsonSets[$field][] = [
                            'jsonKeywords' => $matches['jsonKeywords'],
                            'raw'          => 'CONVERT(\'' . json_encode($v, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) . '\',JSON)',
                        ];
                    }
                }
                else
                {
                    $valueParam = ':' . $k;
                    $setStrs[] = $field . ' = ' . $valueParam;
                    $params[$valueParam] = $v;
                }
            }
        }

        $jsonSets = $this->parseJsonSet($jsonSets);
        if ($setStrs && '' !== $jsonSets)
        {
            $jsonSets = ', ' . $jsonSets;
        }
        $sql = 'update ' . $option->table->toString($query)
            . (($option->partition && '' !== ($partition = $option->partition->toString($query))) ? (' PARTITION(' . $partition . ')') : '')
            . ' set ' . implode(',', $setStrs)
            . $jsonSets
            . $this->parseWhere($option->where, $option->postWhere)
            . $this->parseOrder($option->order)
            . $this->parseLimit($option->offset, $option->limit);

        $query->bindValues($params);

        return $sql;
    }

    public function parseJsonSet(array $jsonSets): string
    {
        if (!$jsonSets)
        {
            return '';
        }
        $result = [];
        foreach ($jsonSets as $field => $options)
        {
            $item = $field . '=JSON_SET(' . $field;
            foreach ($options as $option)
            {
                $item .= ',\'$.' . implode('.', $option['jsonKeywords']) . '\',' . ($option['raw'] ?? $option['valueParam']);
            }
            $result[] = $item . ')';
        }

        return implode(',', $result);
    }
}
