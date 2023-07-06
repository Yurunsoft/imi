<?php

declare(strict_types=1);

namespace Imi\Test\Component\Tests;

use Imi\Test\BaseTest;
use Imi\Test\Component\Model\TestRedisHashObjectColumnTypeModel;
use Imi\Test\Component\Model\TestRedisHashObjectModel;

/**
 * @testdox RedisModel HashObject
 */
class RedisModelHashObjectTest extends BaseTest
{
    public function testSave(): void
    {
        $record = TestRedisHashObjectModel::newInstance([
            'id'    => 1,
            'name'  => 'a',
        ]);
        $record->age = 11;
        $this->assertTrue($record->save());
    }

    public function testFind(): void
    {
        $expected = [
            'id'    => 1,
            'name'  => 'a',
            'age'   => 11,
        ];
        $record = TestRedisHashObjectModel::find([
            'id'    => 1,
            'name'  => 'a',
        ]);
        $this->assertNotNull($record);
        $this->assertEquals($expected, $record->toArray());
    }

    public function testSelect(): void
    {
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
        $record = TestRedisHashObjectModel::newInstance([
            'id'    => 2,
            'name'  => 'b',
            'age'   => 22,
        ]);
        $this->assertTrue($record->save());
        $list = TestRedisHashObjectModel::select([
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
        $record = TestRedisHashObjectModel::find([
            'id'    => 1,
            'name'  => 'a',
        ]);
        $this->assertNotNull($record);
        $this->assertTrue($record->delete());
        $this->assertNull(TestRedisHashObjectModel::find([
            'id'    => 1,
            'name'  => 'a',
        ]));
    }

    public function testSafeDelete(): void
    {
        // --更新--
        // 原始记录
        $record = TestRedisHashObjectModel::newInstance([
            'id'    => 114514,
            'name'  => 'b',
            'age'   => 22,
        ]);
        $this->assertTrue($record->save());

        // 查出2个对象实例
        $record1 = TestRedisHashObjectModel::find([
            'id'    => 114514,
        ]);
        $this->assertNotNull($record1);
        $this->assertEquals($record->toArray(), $record1->toArray());

        $record2 = TestRedisHashObjectModel::find([
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
        $record = TestRedisHashObjectModel::newInstance([
            'id'    => 114514,
            'name'  => 'b',
            'age'   => 22,
        ]);
        $this->assertTrue($record->save());

        // 查出2个对象实例
        $record1 = TestRedisHashObjectModel::find([
            'id'    => 114514,
        ]);
        $this->assertNotNull($record1);
        $this->assertEquals($record->toArray(), $record1->toArray());

        $record2 = TestRedisHashObjectModel::find([
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
        $record = TestRedisHashObjectModel::newInstance([
            'id'    => 1,
            'name'  => 'a',
            'age'   => 11,
        ]);
        $this->assertTrue($record->save());
        $record = TestRedisHashObjectModel::newInstance([
            'id'    => 2,
            'name'  => 'b',
            'age'   => 22,
        ]);
        $this->assertTrue($record->save());
        $this->assertEquals(2, TestRedisHashObjectModel::deleteBatch([
            'id'    => 1,
            'name'  => 'a',
        ], [
            'id'    => 2,
            'name'  => 'b',
        ]));
    }

    public function testColumnType(): void
    {
        $record = TestRedisHashObjectColumnTypeModel::newInstance();
        $record->setJson([
            'name' => 'imi',
        ]);
        $record->setList([1, 2, 3]);
        $record->setSet(['a', 'b', 'c']);
        $this->assertTrue($record->save());
        $this->assertEquals([
            'json'  => [
                'name' => 'imi',
            ],
            'list'  => [1, 2, 3],
            'set'   => ['a', 'b', 'c'],
        ], $record->toArray());

        $record2 = TestRedisHashObjectColumnTypeModel::find();
        $this->assertEquals($record->toArray(), $record2->toArray());
    }
}
