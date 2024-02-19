<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model;

use Imi\Test\Component\Model\TestRedisHashObjectColumnTypeModel;
use Imi\Test\Component\Model\TestRedisHashObjectModel;
use PHPUnit\Framework\TestCase;

abstract class AbstractRedisModelHashObject extends TestCase
{
    protected string $poolName = 'test_phpredis_standalone';
    protected ?string $formatter = null;

    public function testSave(): void
    {
        /** @var class-string<TestRedisHashObjectModel> $model */
        $model = TestRedisHashObjectModel::fork(null, $this->poolName, $this->formatter);
        $record = $model::newInstance([
            'id'    => 1,
            'name'  => 'a',
        ]);
        $record->age = 11;
        $this->assertTrue($record->save());
    }

    public function testFind(): void
    {
        /** @var class-string<TestRedisHashObjectModel> $model */
        $model = TestRedisHashObjectModel::fork(null, $this->poolName, $this->formatter);
        $expected = [
            'id'    => 1,
            'name'  => 'a',
            'age'   => 11,
        ];
        $record = $model::find([
            'id'    => 1,
            'name'  => 'a',
        ]);
        $this->assertNotNull($record);
        $this->assertEquals($expected, $record->toArray());
    }

    public function testSelect(): void
    {
        /** @var class-string<TestRedisHashObjectModel> $model */
        $model = TestRedisHashObjectModel::fork(null, $this->poolName, $this->formatter);
        $expected = [
            [
                'id'    => 1,
                'name'  => 'a',
                'age'   => 11,
            ],
            [
                'id'    => 2,
                'name'  => 'b',
                'age'   => 22,
            ],
        ];
        $record = $model::newInstance([
            'id'    => 2,
            'name'  => 'b',
            'age'   => 22,
        ]);
        $this->assertTrue($record->save());
        $list = $model::select([
            'id'    => 1,
            'name'  => 'a',
        ], [
            'id'    => 2,
            'name'  => 'b',
        ]);
        $this->assertEquals($expected, json_decode(json_encode($list), true));
    }

    public function testDelete(): void
    {
        $model = TestRedisHashObjectModel::fork(null, $this->poolName, $this->formatter);
        $record = $model::find([
            'id'    => 1,
            'name'  => 'a',
        ]);
        $this->assertNotNull($record);
        $this->assertTrue($record->delete());
        $this->assertNull($model::find([
            'id'    => 1,
            'name'  => 'a',
        ]));
    }

    public function testSafeDelete(): void
    {
        /** @var class-string<TestRedisHashObjectModel> $model */
        $model = TestRedisHashObjectModel::fork(null, $this->poolName, $this->formatter);
        // --更新--
        // 原始记录
        $record = $model::newInstance([
            'id'    => 114514,
            'name'  => 'b',
            'age'   => 22,
        ]);
        $this->assertTrue($record->save());

        // 查出2个对象实例
        $record1 = $model::find([
            'id'    => 114514,
        ]);
        $this->assertNotNull($record1);
        $this->assertEquals($record->toArray(), $record1->toArray());

        $record2 = $model::find([
            'id'    => 114514,
        ]);
        $this->assertNotNull($record2);
        $this->assertEquals($record->toArray(), $record2->toArray());

        // 更新一个
        $record1->age = 23;
        $this->assertTrue($record1->save());

        // 安全删除失败
        $this->assertFalse($record2->safeDelete());

        // 安全删除成功
        $this->assertTrue($record1->safeDelete());

        // --删除--
        // 原始记录
        $record = $model::newInstance([
            'id'    => 114514,
            'name'  => 'b',
            'age'   => 22,
        ]);
        $this->assertTrue($record->save());

        // 查出2个对象实例
        $record1 = $model::find([
            'id'    => 114514,
        ]);
        $this->assertNotNull($record1);
        $this->assertEquals($record->toArray(), $record1->toArray());

        $record2 = $model::find([
            'id'    => 114514,
        ]);
        $this->assertNotNull($record2);
        $this->assertEquals($record->toArray(), $record2->toArray());

        // 更新一个
        $this->assertTrue($record1->delete());

        // 安全删除失败
        $this->assertFalse($record2->safeDelete());
    }

    public function testDeleteBatch(): void
    {
        $model = TestRedisHashObjectModel::fork(null, $this->poolName, $this->formatter);
        $record = $model::newInstance([
            'id'    => 1,
            'name'  => 'a',
            'age'   => 11,
        ]);
        $this->assertTrue($record->save());
        $record = $model::newInstance([
            'id'    => 2,
            'name'  => 'b',
            'age'   => 22,
        ]);
        $this->assertTrue($record->save());
        $this->assertEquals(2, $model::deleteBatch([
            'id'    => 1,
            'name'  => 'a',
        ], [
            'id'    => 2,
            'name'  => 'b',
        ]));
    }

    public function testColumnType(): void
    {
        $model = TestRedisHashObjectColumnTypeModel::fork(null, $this->poolName, $this->formatter);
        $record = $model::newInstance();
        $record->setJson([
            'name' => 'imi',
        ]);
        $record->setJsonArray([
            [
                'name' => '宇润',
            ],
            [
                'name' => 'imi',
            ],
        ]);
        $record->setList([1, 2, 3]);
        $record->setSet(['a', 'b', 'c']);
        $this->assertTrue($record->save());
        $this->assertEquals([
            'name' => 'imi',
        ], $record->getJson());
        $this->assertEquals('宇润', $record->getJsonArray()[0]['name']);
        $this->assertEquals('imi', $record->getJsonArray()[1]['name']);
        $this->assertEquals([1, 2, 3], $record->getList());
        $this->assertEquals(['a', 'b', 'c'], $record->getSet());

        $record2 = $model::find();
        $this->assertEquals($record->toArray(), $record2->toArray());
    }
}
