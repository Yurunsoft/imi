<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests;

use Imi\Pool\PoolManager;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\RedisManager;
use Imi\Test\BaseTest;
use PHPUnit\Framework\Assert;

/**
 * @testdox RedisManager
 * @deprecated
 */
class RedisManagerTest extends BaseTest
{
    public const CONNECTION_NAME = 'tradition';

    protected function setUp(): void
    {
        $this->markTestSkipped('Deprecated Test');
    }

    public function testDefaultPoolName(): void
    {
        Assert::assertEquals('redis_test', RedisManager::getDefaultPoolName());
    }

    public function testGetInstance(): void
    {
        $a = RedisManager::getInstance(self::CONNECTION_NAME);
        $b = RedisManager::getInstance(self::CONNECTION_NAME);
        $this->assertEquals(spl_object_id($a), spl_object_id($b));
        $value = 'imi' . bin2hex(random_bytes(4));
        $a->set('test', $value);
        $this->assertEquals($value, $a->get('test'));
    }

    public function testGetNewInstance(): void
    {
        $a = RedisManager::getInstance(self::CONNECTION_NAME);
        $b = RedisManager::getNewInstance(self::CONNECTION_NAME);
        $this->assertNotEquals(spl_object_id($a), spl_object_id($b));
        $value = 'imi' . bin2hex(random_bytes(4));
        $b->set('test', $value);
        $this->assertEquals($value, $b->get('test'));
    }

    public function testNewInstance(): void
    {
        $pool = PoolManager::getInstance('redis_manager_test');
        $instance = null;
        try
        {
            Assert::assertEquals(1, $pool->getCount());
            Assert::assertEquals(1, $pool->getFree());

            $instance = RedisManager::getNewInstance('redis_manager_test');

            Assert::assertEquals(1, $pool->getCount());
            Assert::assertEquals(0, $pool->getFree());
            $this->assertRedisHandler($instance);
        }
        finally
        {
            if (null !== $instance)
            {
                RedisManager::release($instance);
                Assert::assertEquals(1, $pool->getCount());
                Assert::assertEquals(1, $pool->getFree());
            }
        }
    }

    public function testInstance(): void
    {
        $pool = PoolManager::getInstance('redis_manager_test');
        $this->go(function () use ($pool): void {
            Assert::assertEquals(1, $pool->getCount());
            Assert::assertEquals(1, $pool->getFree());

            $instance = RedisManager::getInstance('redis_manager_test');

            Assert::assertEquals(1, $pool->getCount());
            Assert::assertEquals(0, $pool->getFree());

            $this->assertRedisHandler($instance);
        });
        sleep(1);
        Assert::assertEquals(1, $pool->getCount());
        Assert::assertEquals(0, $pool->getFree());
    }

    private function assertRedisHandler(IRedisHandler $redisHandler): void
    {
        Assert::assertInstanceOf(IRedisHandler::class, $redisHandler);
        $str = \bin2hex(random_bytes(8));
        $redisHandler->set('imi:test:a', $str);
        Assert::assertEquals($str, $redisHandler->get('imi:test:a'));
    }
}
