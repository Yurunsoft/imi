<?php

declare(strict_types=1);

namespace Imi\Db\Query\Interfaces;

interface IBaseWhereCollector
{
    /**
     * 设置 where 条件，一般用于 =、>、<、like等.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function where(string $fieldName, string $operation, $value, string $logicalOperator = 'and'): self;

    /**
     * 设置 where 条件，用原生语句.
     *
     * @return static
     */
    public function whereRaw(string $raw, string $logicalOperator = 'and', array $binds = []): self;

    /**
     * 设置 where 条件，传入回调，回调中的条件加括号.
     *
     * @return static
     */
    public function whereBrackets(callable $callback, string $logicalOperator = 'and'): self;

    /**
     * 设置 where 条件，使用 IBaseWhere 结构.
     *
     * @return static
     */
    public function whereStruct(IBaseWhere $where, string $logicalOperator = 'and'): self;

    /**
     * 设置 where 条件，支持语法如下：.
     *
     * [
     *      'id'    => 1,
     *      'or'    => [
     *          'id' => 2,
     *      ],
     *      'title' => ['like', '%test%'],
     *      'age'   => ['>', 18],
     *      'age'   => ['between', 19, 29]
     * ]
     *
     * SQL: id = 1 or (id = 2) and title like '%test%' and age > 18 and age between 19 and 29
     *
     * @return static
     */
    public function whereEx(array $condition, string $logicalOperator = 'and'): self;

    /**
     * where between $begin end $end.
     *
     * @param mixed $begin
     * @param mixed $end
     *
     * @return static
     */
    public function whereBetween(string $fieldName, $begin, $end, string $logicalOperator = 'and'): self;

    /**
     * or where between $begin end $end.
     *
     * @param mixed $begin
     * @param mixed $end
     *
     * @return static
     */
    public function orWhereBetween(string $fieldName, $begin, $end): self;

    /**
     * where not between $begin end $end.
     *
     * @param mixed $begin
     * @param mixed $end
     *
     * @return static
     */
    public function whereNotBetween(string $fieldName, $begin, $end, string $logicalOperator = 'and'): self;

    /**
     * or where not between $begin end $end.
     *
     * @param mixed $begin
     * @param mixed $end
     *
     * @return static
     */
    public function orWhereNotBetween(string $fieldName, $begin, $end): self;

    /**
     * 设置 where or 条件.
     *
     * @param mixed $value
     *
     * @return static
     */
    public function orWhere(string $fieldName, string $operation, $value): self;

    /**
     * 设置 where or 条件，用原生语句.
     *
     * @return static
     */
    public function orWhereRaw(string $where, array $binds = []): self;

    /**
     * 设置 where or 条件，传入回调，回调中的条件加括号.
     *
     * @return static
     */
    public function orWhereBrackets(callable $callback): self;

    /**
     * 设置 where or 条件，使用 IBaseWhere 结构.
     *
     * @return static
     */
    public function orWhereStruct(IBaseWhere $where): self;

    /**
     * 设置 where or 条件，支持语法参考 whereEx 方法.
     *
     * @return static
     */
    public function orWhereEx(array $condition): self;

    /**
     * where field in (list).
     *
     * @return static
     */
    public function whereIn(string $fieldName, array $list, string $logicalOperator = 'and'): self;

    /**
     * or where field in (list).
     *
     * @return static
     */
    public function orWhereIn(string $fieldName, array $list): self;

    /**
     * where field not in (list).
     *
     * @return static
     */
    public function whereNotIn(string $fieldName, array $list, string $logicalOperator = 'and'): self;

    /**
     * or where field not in (list).
     *
     * @return static
     */
    public function orWhereNotIn(string $fieldName, array $list): self;

    /**
     * where field is null.
     *
     * @return static
     */
    public function whereIsNull(string $fieldName, string $logicalOperator = 'and'): self;

    /**
     * or where field is null.
     *
     * @return static
     */
    public function orWhereIsNull(string $fieldName): self;

    /**
     * where field is not null.
     *
     * @return static
     */
    public function whereIsNotNull(string $fieldName, string $logicalOperator = 'and'): self;

    /**
     * or where field is not null.
     *
     * @return static
     */
    public function orWhereIsNotNull(string $fieldName): self;

    /**
     * 后置 Where，and 条件.
     *
     * @return static
     */
    public function postWhere(callable $callback, string $logicalOperator = 'and'): self;

    /**
     * 后置 Where，or 条件.
     *
     * @return static
     */
    public function orPostWhere(callable $callback): self;
}
