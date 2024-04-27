<?php

declare(strict_types=1);

namespace Imi\Test\Component\Tests\Db;

use Imi\Db\Db;
use Imi\Db\Mysql\Consts\LogicalOperator;
use Imi\Db\Mysql\Query\FullText\MysqlFullTextOptions;
use Imi\Db\Mysql\Query\FullText\SearchModifier;
use Imi\Db\Mysql\Query\Lock\MysqlLock;
use Imi\Db\Mysql\Query\Pagination\BigTablePagination;
use Imi\Db\Query\Database;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Db\Query\Interfaces\IWhereCollector;
use Imi\Db\Query\Raw;
use Imi\Db\Query\Where\Where;
use Imi\Test\BaseTest;
use PHPUnit\Framework\Assert;

/**
 * @testdox QueryCurd
 */
abstract class QueryCurdBaseTest extends BaseTest
{
    /**
     * 连接池名.
     *
     * @var string
     */
    protected ?string $poolName = null;

    /**
     * 测试 whereEx 的 SQL.
     *
     * @var string
     */
    protected $expectedTestWhereExSql;

    /**
     * 测试 JSON 查询的 SQL.
     *
     * @var string
     */
    protected $expectedTestJsonSelectSql;

    protected string $fullTableArticle = 'tb_article';

    protected string $tableArticle = 'tb_article';

    protected string $fullTableTestJson = 'tb_test_json';

    protected string $tableTestJson = 'tb_test_json';

    public function testInsert(): array
    {
        Db::getInstance()->exec('truncate ' . $this->fullTableArticle);
        $data = [
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
        ];
        $query = Db::query($this->poolName);

        $result = $query->from($this->tableArticle)->insert($data);
        $id1 = $result->getLastInsertId();
        $record = $query->from($this->tableArticle)->where('id', '=', $id1)->select()->get();
        Assert::assertEquals([
            'id'        => $id1,
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);

        $result = $query->setData([
            'title'     => 'title-insert',
            'content'   => 'content-insert',
        ])
        ->setField('time', '2019-06-21 00:00:00')
        ->from($this->tableArticle)
        ->insert();
        $id2 = $result->getLastInsertId();
        $record = $query->from($this->tableArticle)->where('id', '=', $id2)->select()->get();
        Assert::assertEquals([
            'id'        => $id2,
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);

        return [
            'ids' => [$id1, $id2],
        ];
    }

    /**
     * @depends testInsert
     */
    public function testSelectGet(array $args): void
    {
        ['ids' => $ids] = $args;
        $query = Db::query($this->poolName);
        $record = $query->from($this->tableArticle)->where('id', '=', $ids[0])->select()->get();
        Assert::assertEquals([
            'id'        => $ids[0],
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
    }

    /**
     * @depends testInsert
     */
    public function testSelectGetArray(array $args): void
    {
        ['ids' => $ids] = $args;
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->whereIn('id', $ids)->select();
        $record = $result->getArray();
        Assert::assertEquals(2, $result->getRowCount());
        Assert::assertEquals([
            [
                'id'        => $ids[0],
                'title'     => 'title-insert',
                'content'   => 'content-insert',
                'time'      => '2019-06-21 00:00:00',
                'member_id' => 0,
            ],
            [
                'id'        => $ids[1],
                'title'     => 'title-insert',
                'content'   => 'content-insert',
                'time'      => '2019-06-21 00:00:00',
                'member_id' => 0,
            ],
        ], $record);
    }

    /**
     * @depends testInsert
     */
    public function testSelectGetColumn(array $args): void
    {
        ['ids' => $ids] = $args;
        $query = Db::query($this->poolName);
        $record = $query->from($this->tableArticle)->whereIn('id', $ids)->select()->getColumn();
        Assert::assertEquals($ids, $record);
    }

    /**
     * @depends testInsert
     */
    public function testSelectGetScalar(array $args): void
    {
        ['ids' => $ids] = $args;
        $query = Db::query($this->poolName);
        $record = $query->from($this->tableArticle)->where('id', '=', $ids[1])->field('id')->select()->getScalar();
        Assert::assertEquals($ids[1], $record);
    }

    /**
     * @depends testInsert
     */
    public function testPaginate(array $args): void
    {
        ['ids' => $ids] = $args;
        $expectedData = [
            'list'          => [
                [
                    'id'        => $ids[1],
                    'title'     => 'title-insert',
                    'content'   => 'content-insert',
                    'time'      => '2019-06-21 00:00:00',
                    'member_id' => 0,
                ],
            ],
            'limit'         => 1,
            'total'         => 2,
            'page_count'    => 2,
        ];
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->paginate(2, 1);
        $this->assertEquals($expectedData, $result->toArray());
        $this->assertEquals($expectedData['list'], $result->getList());
        $this->assertEquals($expectedData['total'], $result->getTotal());
        $this->assertEquals($expectedData['limit'], $result->getLimit());
        $this->assertEquals($expectedData['page_count'], $result->getPageCount());

        // distinct
        $result = $query->distinct()->field('member_id')->from($this->tableArticle)->paginate(1, 1);
        $expectedData = [
            'list' => [
                [
                    'member_id' => 0,
                ],
            ],
            'limit'      => 1,
            'total'      => 1,
            'page_count' => 1,
        ];
        $this->assertEquals($expectedData, $result->toArray());
        $this->assertEquals($expectedData['list'], $result->getList());
        $this->assertEquals($expectedData['total'], $result->getTotal());
        $this->assertEquals($expectedData['limit'], $result->getLimit());
        $this->assertEquals($expectedData['page_count'], $result->getPageCount());

        // distinct field
        $result = $query->distinct()->field('member_id')->from($this->tableArticle)->paginate(1, 1, [
            'countField' => 'null',
        ]);
        $expectedData = [
            'list' => [
                [
                    'member_id' => 0,
                ],
            ],
            'limit'      => 1,
            'total'      => 0,
            'page_count' => 0,
        ];
        $this->assertEquals($expectedData, $result->toArray());
        $this->assertEquals($expectedData['list'], $result->getList());
        $this->assertEquals($expectedData['total'], $result->getTotal());
        $this->assertEquals($expectedData['limit'], $result->getLimit());
        $this->assertEquals($expectedData['page_count'], $result->getPageCount());
    }

    /**
     * @depends testInsert
     */
    public function testBigTablePagination(array $args): void
    {
        ['ids' => $ids] = $args;
        $expectedData = [
            'list'          => [
                [
                    'id'        => $ids[1],
                    'title'     => 'title-insert',
                    'content'   => 'content-insert',
                    'time'      => '2019-06-21 00:00:00',
                    'member_id' => 0,
                ],
            ],
            'limit'         => 1,
            'total'         => 2,
            'page_count'    => 2,
        ];
        $query = Db::query($this->poolName)->from($this->tableArticle)->whereRaw('1=1');
        $pagination = new BigTablePagination($query);
        $this->assertEquals($query, $pagination->getQuery());
        $this->assertEquals('id', $pagination->getIdField());
        $this->assertTrue($pagination->isCleanWhere());
        $this->assertEquals($expectedData['list'], $pagination->select(2, 1)->getArray());

        $this->assertEquals([], $pagination->select(100, 1)->getArray());

        $result = $pagination->paginate(2, 1);
        $this->assertEquals($expectedData, $result->toArray());
        $this->assertEquals($expectedData['list'], $result->getList());
        $this->assertEquals($expectedData['total'], $result->getTotal());
        $this->assertEquals($expectedData['limit'], $result->getLimit());
        $this->assertEquals($expectedData['page_count'], $result->getPageCount());

        $result = $pagination->paginate(100, 1);
        $tmpExceptedData = $expectedData;
        $tmpExceptedData['list'] = [];
        $this->assertEquals($tmpExceptedData, $result->toArray());
        $this->assertEquals($tmpExceptedData['list'], $result->getList());
        $this->assertEquals($tmpExceptedData['total'], $result->getTotal());
        $this->assertEquals($tmpExceptedData['limit'], $result->getLimit());
        $this->assertEquals($tmpExceptedData['page_count'], $result->getPageCount());

        $pagination = new BigTablePagination($query, 'id', false);
        $this->assertEquals($query, $pagination->getQuery());
        $this->assertEquals('id', $pagination->getIdField());
        $this->assertFalse($pagination->isCleanWhere());
        $this->assertEquals($expectedData['list'], $pagination->select(2, 1)->getArray());

        $query = Db::query($this->poolName)->from($this->tableArticle)->whereRaw('1=2');
        $pagination = new BigTablePagination($query);
        $this->assertEquals($query, $pagination->getQuery());
        $this->assertEquals('id', $pagination->getIdField());
        $this->assertTrue($pagination->isCleanWhere());
        $this->assertEquals([], $pagination->select(2, 1)->getArray());

        $pagination = new BigTablePagination($query, 'id', false);
        $this->assertEquals($query, $pagination->getQuery());
        $this->assertEquals('id', $pagination->getIdField());
        $this->assertFalse($pagination->isCleanWhere());
        $this->assertEquals([], $pagination->select(2, 1)->getArray());
    }

    /**
     * @depends testInsert
     *
     * @see https://github.com/imiphp/imi/issues/58
     */
    public function testBug58(array $args): void
    {
        ['ids' => $ids] = $args;
        $expectedData = [
            'list'          => [
                [
                    'id'        => $ids[1],
                    'title'     => 'title-insert',
                    'content'   => 'content-insert',
                    'time'      => '2019-06-21 00:00:00',
                    'member_id' => 0,
                ],
            ],
            'limit'         => 1,
            'total'         => 1,
            'page_count'    => 1,
        ];
        $result = Db::query($this->poolName)->from($this->tableArticle)
                             ->bindValues([
                                 ':id'  => $ids[1],
                             ])
                             ->whereRaw('id = :id')
                             ->paginate(1, 1);
        $this->assertEquals($expectedData, $result->toArray());
    }

    /**
     * @depends testInsert
     */
    public function testPaginateNoTotal(array $args): void
    {
        ['ids' => $ids] = $args;
        $expectedData = [
            'list'          => [
                [
                    'id'        => $ids[0],
                    'title'     => 'title-insert',
                    'content'   => 'content-insert',
                    'time'      => '2019-06-21 00:00:00',
                    'member_id' => 0,
                ],
                [
                    'id'        => $ids[1],
                    'title'     => 'title-insert',
                    'content'   => 'content-insert',
                    'time'      => '2019-06-21 00:00:00',
                    'member_id' => 0,
                ],
            ],
            'limit'         => 2,
        ];
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->paginate(1, 2, [
            'total' => false,
        ]);
        $this->assertEquals($expectedData, $result->toArray());
        $this->assertEquals([
            [
                'id'        => $ids[0],
                'title'     => 'title-insert',
                'content'   => 'content-insert',
                'time'      => '2019-06-21 00:00:00',
                'member_id' => 0,
            ],
            [
                'id'        => $ids[1],
                'title'     => 'title-insert',
                'content'   => 'content-insert',
                'time'      => '2019-06-21 00:00:00',
                'member_id' => 0,
            ],
        ], $result->getList());
        $this->assertNull($result->getTotal());
        $this->assertEquals(2, $result->getLimit());
        $this->assertNull($result->getPageCount());
    }

    public function testUpdate(): void
    {
        $data = [
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
        ];
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->insert($data);
        $id = $result->getLastInsertId();

        $result = $query->from($this->tableArticle)->where('id', '=', $id)->update([
            'content'   => 'imi',
            'time'      => '2018-06-21 00:00:00',
        ]);
        Assert::assertEquals(1, $result->getAffectedRows());
        $record = $query->from($this->tableArticle)->where('id', '=', $id)->select()->get();
        Assert::assertEquals([
            'id'        => $id,
            'title'     => 'title-insert',
            'content'   => 'imi',
            'time'      => '2018-06-21 00:00:00',
            'member_id' => 0,
        ], $record);

        $result = $query->from($this->tableArticle)->where('id', '=', $id)->setData([
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
        ])->update();
        Assert::assertEquals(1, $result->getAffectedRows());
        $record = $query->from($this->tableArticle)->where('id', '=', $id)->select()->get();
        Assert::assertEquals([
            'id'        => $id,
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
    }

    public function testDelete(): void
    {
        $data = [
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
        ];
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->insert($data);
        $id = $result->getLastInsertId();

        $result = $query->from($this->tableArticle)->where('id', '=', $id)->delete();
        Assert::assertEquals(1, $result->getAffectedRows());

        $record = $query->from($this->tableArticle)->where('id', '=', $id)->select()->get();
        Assert::assertNull($record);
    }

    public function testWhere(): void
    {
        $query = Db::query($this->poolName);
        $sql = $query->from($this->tableArticle)->where('where', '=', 1)
                                                ->orWhere('orWhere', '=', 2)
                                                ->whereBetween('whereBetween', 1, 2)
                                                ->orWhereBetween('orWhereBetween', 1, 2)
                                                ->whereNotBetween('whereNotBetween', 1, 2)
                                                ->orWhereNotBetween('orWhereNotBetween', 1, 2)
                                                ->whereIn('whereIn', [1, 2])
                                                ->orWhereIn('orWhereIn', [1, 2])
                                                ->whereNotIn('whereNotIn', [1, 2])
                                                ->orWhereNotIn('orWhereNotIn', [1, 2])
                                                ->whereIsNull('whereIsNull')
                                                ->orWhereIsNull('orWhereIsNull')
                                                ->whereIsNotNull('whereIsNotNull')
                                                ->orWhereIsNotNull('orWhereIsNotNull')
                                                ->whereRaw('whereRaw = 1')
                                                ->orWhereRaw('orWhereRaw = 2')
                                                ->whereStruct(new Where('whereStruct', '=', 1))
                                                ->buildSelectSql();
        Assert::assertEquals(<<<SQL
        select * from `{$this->fullTableArticle}` where `where` = :p1 or `orWhere` = :p2 and `whereBetween` between :p3 and :p4 or `orWhereBetween` between :p5 and :p6 and `whereNotBetween` not between :p7 and :p8 or `orWhereNotBetween` not between :p9 and :pa and `whereIn` in (:pb,:pc) or `orWhereIn` in (:pd,:pe) and `whereNotIn` not in (:pf,:p10) or `orWhereNotIn` not in (:p11,:p12) and `whereIsNull` is null or `orWhereIsNull` is null and `whereIsNotNull` is not null or `orWhereIsNotNull` is not null and whereRaw = 1 or orWhereRaw = 2 and `whereStruct` = :p13
        SQL, $sql);
    }

    /**
     * @depends testInsert
     */
    public function testWhereEx(array $args): void
    {
        ['ids' => $ids] = $args;
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->whereEx([
            'id'    => $ids[0],
            'and'   => [
                'id'    => ['in', [$ids[0]]],
            ],
        ])->select();
        // 多条件SQL
        Assert::assertEquals($this->expectedTestWhereExSql, $result->getSql());
        // 查询记录
        $record = $result->get();
        Assert::assertEquals([
            'id'        => $ids[0],
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
        // BUG: https://github.com/imiphp/imi/pull/25
        Assert::assertEquals('select * from `' . $this->fullTableArticle . '`', Db::query($this->poolName)->from($this->tableArticle)->whereEx([])->select()->getSql());
    }

    /**
     * @depends testInsert
     */
    public function testWhereBrackets(array $args): void
    {
        ['ids' => $ids] = $args;
        $id = $ids[1];

        // 字符串
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->whereBrackets(static fn () => 'id=' . $id)->select();
        $record = $result->get();
        Assert::assertEquals([
            'id'        => $id,
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
        Assert::assertEquals('select * from `tb_article` where (id=2)', $result->getSql());

        // Where 数组
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->whereBrackets(static fn () => [
            new Where('id', '=', $id),
            (static function () {
                $where = new Where();
                $where->setRawSQL('1=2');
                $where->setLogicalOperator(LogicalOperator::OR);
                $where->useRaw();

                return $where;
            })(),
        ])->select();
        $record = $result->get();
        Assert::assertEquals([
            'id'        => $id,
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
        Assert::assertEquals('select * from `tb_article` where (`id` = :p1 or 1=2)', $result->getSql());
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->whereBrackets(static fn () => [
            new Where('id', '=', $id),
            'or 1=2',
        ])->select();
        $record = $result->get();
        Assert::assertEquals([
            'id'        => $id,
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
        Assert::assertEquals('select * from `tb_article` where (`id` = :p1 or 1=2)', $result->getSql());

        // Where 对象
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->whereBrackets(static fn () => new Where('id', '=', $id))->select();
        $record = $result->get();
        Assert::assertEquals([
            'id'        => $id,
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
        Assert::assertEquals('select * from `tb_article` where (`id` = :p1)', $result->getSql());

        // Where 收集器
        $query = Db::query($this->poolName);
        $result = $query->from($this->tableArticle)->whereBrackets(static function (IQuery $query, IWhereCollector $where) use ($id) {
            $where->where('id', '=', $id)->orWhereRaw('1=2');
        })->select();
        $record = $result->get();
        Assert::assertEquals([
            'id'        => $id,
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
        Assert::assertEquals('select * from `tb_article` where (`id` = :p1 or 1=2)', $result->getSql());

        $query = Db::query($this->poolName);
        $sql = $query->from($this->tableArticle)->whereBrackets(static function (IQuery $query, IWhereCollector $where) {
            $where->where('where', '=', 1)
                    ->orWhere('orWhere', '=', 2)
                    ->whereBetween('whereBetween', 1, 2)
                    ->orWhereBetween('orWhereBetween', 1, 2)
                    ->whereNotBetween('whereNotBetween', 1, 2)
                    ->orWhereNotBetween('orWhereNotBetween', 1, 2)
                    ->whereIn('whereIn', [1, 2])
                    ->orWhereIn('orWhereIn', [1, 2])
                    ->whereNotIn('whereNotIn', [1, 2])
                    ->orWhereNotIn('orWhereNotIn', [1, 2])
                    ->whereIsNull('whereIsNull')
                    ->orWhereIsNull('orWhereIsNull')
                    ->whereIsNotNull('whereIsNotNull')
                    ->orWhereIsNotNull('orWhereIsNotNull')
                    ->whereRaw('whereRaw = 1')
                    ->orWhereRaw('orWhereRaw = 2')
                    ->whereStruct(new Where('whereStruct', '=', 1))
                    ->whereEx([
                        'whereEx'    => 1,
                        'and'        => [
                            'whereEx'    => ['in', [1]],
                        ],
                    ])
                    ->orWhereEx([
                        'orWhereEx'    => 1,
                        'or'           => [
                            'orWhereEx'    => ['in', [1]],
                        ],
                    ]);
        })->buildSelectSql();
        Assert::assertEquals(<<<SQL
        select * from `{$this->fullTableArticle}` where (`where` = :p1 or `orWhere` = :p2 and `whereBetween` between :p3 and :p4 or `orWhereBetween` between :p5 and :p6 and `whereNotBetween` not between :p7 and :p8 or `orWhereNotBetween` not between :p9 and :pa and `whereIn` in (:pb,:pc) or `orWhereIn` in (:pd,:pe) and `whereNotIn` not in (:pf,:p10) or `orWhereNotIn` not in (:p11,:p12) and `whereIsNull` is null or `orWhereIsNull` is null and `whereIsNotNull` is not null or `orWhereIsNotNull` is not null and whereRaw = 1 or orWhereRaw = 2 and `whereStruct` = :p13 and (`whereEx` = :p14 and (`whereEx` in (:p15))) or (`orWhereEx` = :p16 or (`orWhereEx` in (:p17))))
        SQL, $sql);
    }

    /**
     * @depends testInsert
     */
    public function testLock(array $args): void
    {
        ['ids' => $ids] = $args;
        $query = Db::query($this->poolName);
        $record = $query->from($this->tableArticle)->where('id', '=', 1)->lock(MysqlLock::FOR_UPDATE)->select()->get();
        Assert::assertEquals([
            'id'        => $ids[0],
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);

        $query = Db::query($this->poolName);
        $record = $query->from($this->tableArticle)->where('id', '=', 1)->lock(MysqlLock::SHARED)->select()->get();
        Assert::assertEquals([
            'id'        => $ids[0],
            'title'     => 'title-insert',
            'content'   => 'content-insert',
            'time'      => '2019-06-21 00:00:00',
            'member_id' => 0,
        ], $record);
    }

    /**
     * @depends testInsert
     */
    public function testRawAlias(array $args): void
    {
        ['ids' => $ids] = $args;
        $query = Db::query($this->poolName);
        $record = $query->from($this->tableArticle)->whereIsNotNull('id')->field('id')->fieldRaw('title')->fieldRaw('id + 1', 'id2')->select()->get();
        Assert::assertEquals([
            'id'    => $ids[0],
            'title' => 'title-insert',
            'id2'   => $ids[0] + 1,
        ], $record);
    }

    public function testJson(): void
    {
        $query = Db::query($this->poolName);
        $jsonStr = '{"uid": "' . ($uid = uniqid('', true)) . '", "name": "aaa", "list1": [{"id": 1}], "测试": {"值": "imi"}}';
        // 插入数据
        $insertResult = $query->from($this->tableTestJson)->insert([
            'json_data' => $jsonStr,
        ]);
        $id = $insertResult->getLastInsertId();
        // 查询条件
        $result = $query->from($this->tableTestJson)->where('json_data->uid', '=', $uid)->select();
        $this->assertEquals([
            'id'        => $id,
            'json_data' => $jsonStr,
        ], $result->get());
        $this->assertEquals($this->expectedTestJsonSelectSql, $result->getSql());
        // 更新数据
        $query->from($this->tableTestJson)->where('json_data->uid', '=', $uid)->update([
            'json_data->a'           => '1',
            'json_data->name'        => 'bbb',
            'json_data->list1[0].id' => '2',
            'json_data->list2'       => [1, 2, 3],
            'json_data->"测试"."值"'    => 'imi niubi',
        ]);
        $result = $query->from($this->tableTestJson)->where('json_data->uid', '=', $uid)->order('json_data->uid')->select();
        $this->assertEquals([
            'id'        => $id,
            'json_data' => '{"a": "1", "uid": "' . $uid . '", "name": "bbb", "list1": [{"id": "2"}], "list2": [1, 2, 3], "测试": {"值": "imi niubi"}}',
        ], $result->get());
    }

    /**
     * @depends testInsert
     */
    public function testRaw(array $args): void
    {
        ['ids' => $ids] = $args;
        $id = $ids[0];
        $query = Db::query($this->poolName);

        $record = $query->from($this->tableArticle)->where('id', '=', new Raw((string) $id))->select()->get();
        Assert::assertEquals($id, $record['id']);
    }

    public function testPartition(): void
    {
        $query = Db::query()->from('test')->partition(['a', 'b']);
        $this->assertEquals('select * from `test` PARTITION(`a`,`b`)', $query->buildSelectSql());
        $this->assertEquals('insert into `test` PARTITION(`a`,`b`) (`value`) values(:value)', $query->buildInsertSql(['value' => 123]));
        $this->assertEquals('insert into `test` PARTITION(`a`,`b`) (`value`) values (:p1),(:p2)', $query->buildBatchInsertSql([['value' => 123], ['value' => 456]]));
        $this->assertEquals('update `test` PARTITION(`a`,`b`) set `value` = :value', $query->buildUpdateSql(['value' => 123]));
        $this->assertEquals('delete from `test` PARTITION(`a`,`b`)', $query->buildDeleteSql());
        $query->getOption()->options['ignore'] = true;
        $this->assertEquals('insert ignore into `test` PARTITION(`a`,`b`) (`value`) values(:value)', $query->buildInsertSql(['value' => 123]));
        $this->assertEquals('insert ignore into `test` PARTITION(`a`,`b`) (`value`) values (:p1),(:p2)', $query->buildBatchInsertSql([['value' => 123], ['value' => 456]]));

        $query = Db::query()->from('test')->partitionRaw('`a`,`b`');
        $this->assertEquals('select * from `test` PARTITION(`a`,`b`)', $query->buildSelectSql());
        $this->assertEquals('insert into `test` PARTITION(`a`,`b`) (`value`) values(:value)', $query->buildInsertSql(['value' => 123]));
        $this->assertEquals('insert into `test` PARTITION(`a`,`b`) (`value`) values (:p1),(:p2)', $query->buildBatchInsertSql([['value' => 123], ['value' => 456]]));
        $this->assertEquals('update `test` PARTITION(`a`,`b`) set `value` = :value', $query->buildUpdateSql(['value' => 123]));
        $this->assertEquals('delete from `test` PARTITION(`a`,`b`)', $query->buildDeleteSql());
        $query->getOption()->options['ignore'] = true;
        $this->assertEquals('insert ignore into `test` PARTITION(`a`,`b`) (`value`) values(:value)', $query->buildInsertSql(['value' => 123]));
        $this->assertEquals('insert ignore into `test` PARTITION(`a`,`b`) (`value`) values (:p1),(:p2)', $query->buildBatchInsertSql([['value' => 123], ['value' => 456]]));
    }

    public function testRawBinds(): void
    {
        $query = Db::query()->from('test')
                            ->fieldRaw('test.*, ?', null, ['imi'])
                            ->joinRaw('join test2 on test.id = test2.id and test2.id2 = ?', [1])
                            ->whereRaw('test.id = ?', 'and', [2])
                            ->orWhereRaw('test.id = ?', [3])
                            ->groupRaw('test.id, ?', [4])
                            ->havingRaw('test.id = ?', 'and', [5])
                            ->orderRaw('field(test.id, ?, ?)', [6, 7])
        ;
        $this->assertEquals('select test.*, ? from `test` join test2 on test.id = test2.id and test2.id2 = ? where test.id = ? or test.id = ? group by test.id, ? having test.id = ? order by field(test.id, ?, ?)', $query->buildSelectSql());
        $this->assertEquals(['imi', 1, 2, 3, 4, 5, 6, 7], $query->getBinds());
    }

    public function testSetFieldExp(): void
    {
        $query = Db::query()->from('test')->setFieldExp('c', '1 + ?', [1])
        ;
        $this->assertEquals('insert into `test` (`c`) values(1 + ?)', $query->buildInsertSql());
        $this->assertEquals([1], $query->getBinds());

        $query = Db::query()->from('test')->setFieldInc('a', 1)
                                          ->setFieldDec('b', 2)
                                          ->setFieldExp('c', 'c + :c', [':c' => 3])
        ;
        $this->assertEquals('update `test` set `a` = `a` + :fip1,`b` = `b` - :fdp2,`c` = c + :c', $query->buildUpdateSql());
        $this->assertEquals([':fip1' => 1, ':fdp2' => 2, ':c' => 3], $query->getBinds());

        $query = Db::query()->from('test')->setFieldInc('a', 4)
                                          ->setFieldDec('b', 5)
                                          ->setFieldExp('c', 'c + :c', [':c' => 6])
        ;
        $this->assertEquals('replace into `test` set `a` = `a` + :fip1,`b` = `b` - :fdp2,`c` = c + :c', $query->buildReplaceSql());
        $this->assertEquals([':fip1' => 4, ':fdp2' => 5, ':c' => 6], $query->getBinds());
    }

    public function testDatabase(): void
    {
        $query = Db::query();

        $database = new Database();
        $this->assertNull($database->getDatabase());
        $this->assertNull($database->getAlias());
        $this->assertEquals('', $database->toString($query));

        $database->setDatabase('db_imi');
        $this->assertEquals('db_imi', $database->getDatabase());
        $this->assertNull($database->getAlias());
        $this->assertEquals('`db_imi`', $database->toString($query));

        $database->setAlias('a');
        $this->assertEquals('db_imi', $database->getDatabase());
        $this->assertEquals('a', $database->getAlias());
        $this->assertEquals('`db_imi` as `a`', $database->toString($query));

        $database->useRaw(true);
        $this->assertTrue($database->isRaw());
        $database->setRawSQL('db_imi2');
        $this->assertEquals('(db_imi2) as `a`', $database->toString($query));
        $database->setAlias(null);
        $this->assertNull($database->getAlias());
        $this->assertEquals('db_imi2', $database->toString($query));
    }

    public function testJoinWhere(): void
    {
        $query = Db::query()->from('test')
            ->join('test2', 'test.id', '=', 'test2.id', null, new Where('test2.id2', '=', 1));
        $this->assertEquals('select * from `test` inner join `test2` on `test`.`id`=`test2`.`id` and `test2`.`id2` = :p1', $query->buildSelectSql());
        $this->assertEquals([':p1' => 1], $query->getBinds());
    }

    public function testFullTextSearch(): void
    {
        $query = Db::query()->from('test')->fullText('content', 'imi');
        $this->assertEquals('select * from `test` where MATCH (`content`) AGAINST (:p1) > :p2', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 0], $query->getBinds());

        $query = Db::query()->from('test')->fullText(['title', 'content'], 'imi');
        $this->assertEquals('select * from `test` where MATCH (`title`,`content`) AGAINST (:p1) > :p2', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 0], $query->getBinds());

        $query = Db::query()->from('test')->where('member_id', '=', 1)->fullText('content', 'imi');
        $this->assertEquals('select * from `test` where `member_id` = :p1 and MATCH (`content`) AGAINST (:p2) > :p3', $query->buildSelectSql());
        $this->assertEquals([':p1' => 1, ':p2' => 'imi', ':p3' => 0], $query->getBinds());

        $query = Db::query()->from('test')->where('member_id', '=', 1)->fullText(['title', 'content'], 'imi');
        $this->assertEquals('select * from `test` where `member_id` = :p1 and MATCH (`title`,`content`) AGAINST (:p2) > :p3', $query->buildSelectSql());
        $this->assertEquals([':p1' => 1, ':p2' => 'imi', ':p3' => 0], $query->getBinds());

        $query = Db::query()->from('test')->where('member_id', '=', 1)->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setMinScore(0.25)->setWhereLogicalOperator('or'));
        $this->assertEquals('select * from `test` where `member_id` = :p1 or MATCH (`content`) AGAINST (:p2) > :p3', $query->buildSelectSql());
        $this->assertEquals([':p1' => 1, ':p2' => 'imi', ':p3' => 0.25], $query->getBinds());
        $this->assertEquals(0.25, $options->getMinScore());
        $this->assertEquals('or', $options->getWhereLogicalOperator());

        $query = Db::query()->from('test')->field('*')->where('member_id', '=', 1)->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setMinScore(0.25)->setWhereLogicalOperator('or')->setScoreFieldName(''));
        $this->assertEquals('select *,MATCH (`content`) AGAINST (:p1) from `test` where `member_id` = :p2 or MATCH (`content`) AGAINST (:p3) > :p4', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 1, ':p3' => 'imi', ':p4' => 0.25], $query->getBinds());
        $this->assertEquals(0.25, $options->getMinScore());
        $this->assertEquals('or', $options->getWhereLogicalOperator());
        $this->assertEquals('', $options->getScoreFieldName());

        $query = Db::query()->from('test')->field('*')->where('member_id', '=', 1)->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setMinScore(0.25)->setWhereLogicalOperator('or')->setScoreFieldName('score'));
        $this->assertEquals('select *,(MATCH (`content`) AGAINST (:p1)) as `score` from `test` where `member_id` = :p2 or MATCH (`content`) AGAINST (:p3) > :p4', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 1, ':p3' => 'imi', ':p4' => 0.25], $query->getBinds());
        $this->assertEquals(0.25, $options->getMinScore());
        $this->assertEquals('or', $options->getWhereLogicalOperator());
        $this->assertEquals('score', $options->getScoreFieldName());

        $query = Db::query()->from('test')->field('*')->where('member_id', '=', 1)->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setMinScore(0.25)->setWhereLogicalOperator('or')->setScoreFieldName('')->setOrderDirection('desc'));
        $this->assertEquals('select *,MATCH (`content`) AGAINST (:p1) from `test` where `member_id` = :p3 or MATCH (`content`) AGAINST (:p4) > :p5 order by (MATCH (`content`) AGAINST (:p2)) desc', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 'imi', ':p3' => 1, ':p4' => 'imi', ':p5' => 0.25], $query->getBinds());
        $this->assertEquals(0.25, $options->getMinScore());
        $this->assertEquals('or', $options->getWhereLogicalOperator());
        $this->assertEquals('', $options->getScoreFieldName());
        $this->assertEquals('desc', $options->getOrderDirection());

        $query = Db::query()->from('test')->field('*')->where('member_id', '=', 1)->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setMinScore(0.25)->setWhereLogicalOperator('or')->setScoreFieldName('score')->setOrderDirection('desc'));
        $this->assertEquals('select *,(MATCH (`content`) AGAINST (:p1)) as `score` from `test` where `member_id` = :p2 or MATCH (`content`) AGAINST (:p3) > :p4 order by `score` desc', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 1, ':p3' => 'imi', ':p4' => 0.25], $query->getBinds());
        $this->assertEquals(0.25, $options->getMinScore());
        $this->assertEquals('or', $options->getWhereLogicalOperator());
        $this->assertEquals('score', $options->getScoreFieldName());
        $this->assertEquals('desc', $options->getOrderDirection());

        $query = Db::query()->from('test')->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setSearchModifier(SearchModifier::IN_NATURAL_LANGUAGE_MODE));
        $this->assertEquals('select * from `test` where MATCH (`content`) AGAINST (:p1 IN NATURAL LANGUAGE MODE) > :p2', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 0], $query->getBinds());
        $this->assertEquals(SearchModifier::IN_NATURAL_LANGUAGE_MODE, $options->getSearchModifier());

        $query = Db::query()->from('test')->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setSearchModifier(SearchModifier::IN_NATURAL_LANGUAGE_MODE_WITH_QUERY_EXPANSION));
        $this->assertEquals('select * from `test` where MATCH (`content`) AGAINST (:p1 IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION) > :p2', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 0], $query->getBinds());
        $this->assertEquals(SearchModifier::IN_NATURAL_LANGUAGE_MODE_WITH_QUERY_EXPANSION, $options->getSearchModifier());

        $query = Db::query()->from('test')->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setSearchModifier(SearchModifier::IN_BOOLEAN_MODE));
        $this->assertEquals('select * from `test` where MATCH (`content`) AGAINST (:p1 IN BOOLEAN MODE) > :p2', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 0], $query->getBinds());
        $this->assertEquals(SearchModifier::IN_BOOLEAN_MODE, $options->getSearchModifier());

        $query = Db::query()->from('test')->fullText('content', 'imi', $options = (new MysqlFullTextOptions())->setSearchModifier(SearchModifier::WITH_QUERY_EXPANSION));
        $this->assertEquals('select * from `test` where MATCH (`content`) AGAINST (:p1 WITH QUERY EXPANSION) > :p2', $query->buildSelectSql());
        $this->assertEquals([':p1' => 'imi', ':p2' => 0], $query->getBinds());
        $this->assertEquals(SearchModifier::WITH_QUERY_EXPANSION, $options->getSearchModifier());
    }

    public function testWhereNull(): void
    {
        $query = Db::query()->from('test')->where('a', 'is', null);
        $this->assertEquals('select * from `test` where `a` is NULL', $query->buildSelectSql());
        $this->assertEquals([], $query->getBinds());
    }

    public function testPostWhere(): void
    {
        $query = Db::query()->from('test')
                            ->postWhere(static function (IQuery $query, IWhereCollector $whereCollector) {
                                $whereCollector->where('b', '=', 2)
                                               ->orWhere('c', '=', 3);
                            })
                            ->postWhere(static fn () => new Where('d', '!=', 4, 'or'))
                            ->where('a', '=', 1);
        $this->assertEquals('select * from `test` where `a` = :p1 and (`b` = :p2 or `c` = :p3) and (`d` != :p4)', $query->buildSelectSql());
        $this->assertEquals([
            ':p1' => 1,
            ':p2' => 2,
            ':p3' => 3,
            ':p4' => 4,
        ], $query->getBinds());
    }
}
