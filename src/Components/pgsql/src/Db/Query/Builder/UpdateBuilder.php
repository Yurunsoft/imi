<?php

declare(strict_types=1);

namespace Imi\Pgsql\Db\Query\Builder;

use Imi\Db\Query\QueryOption;

/**
 * @property \Imi\Pgsql\Db\Query\PgsqlQuery $query
 */
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
                            'valueParam'   => $this->parseValueParam($valueParam, $v),
                        ];
                        $params[$valueParam] = $v;
                    }
                    else
                    {
                        $jsonSets[$field][] = [
                            'jsonKeywords' => $matches['jsonKeywords'],
                            'raw'          => 'jsonb(\'' . json_encode($v, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) . '\'::TEXT)',
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
        $sql = 'update ' . $option->table->toString($query) . ' set ' . implode(',', $setStrs)
            . $jsonSets
            . $this->parseWhere($option->where, $option->postWhere)
            . $this->parseOrder($option->order);

        $query->bindValues($params);

        return $sql;
    }

    /**
     * @param mixed $value
     */
    protected function parseValueParam(string $valueParam, $value): string
    {
        if (\is_bool($value))
        {
            $type = 'BOOL';
        }
        elseif (\is_int($value))
        {
            $type = 'INT8';
        }
        elseif (\is_float($value))
        {
            $type = 'FLOAT8';
        }
        else
        {
            $type = 'TEXT';
        }

        return "cast({$valueParam} as {$type})";
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
            $item = '';
            foreach (array_reverse($options) as $option)
            {
                if (isset($option['raw']))
                {
                    $value = $option['raw'];
                }
                else
                {
                    $value = 'to_jsonb(' . $option['valueParam'] . ')';
                }
                if ('' === $item)
                {
                    $item = 'jsonb_set(to_jsonb(' . $field . '), \'{' . $this->query->parseToJsonKeywordsStr($option['jsonKeywords']) . '}\', ' . $value . ', true)';
                }
                else
                {
                    $item = 'jsonb_set(' . $item . ', \'{' . $this->query->parseToJsonKeywordsStr($option['jsonKeywords']) . '}\', ' . $value . ', true)';
                }
            }
            $result[] = $field . '=' . $item;
        }

        return implode(',', $result);
    }
}
