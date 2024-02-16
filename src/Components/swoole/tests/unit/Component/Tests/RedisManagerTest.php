<?php

declare(strict_types=1);

namespace Imi\Swoole\Test\Component\Tests;

use Imi\Pool\PoolManager;
use Imi\Redis\RedisHandler;
use Imi\Redis\RedisManager;
use Imi\Swoole\Test\BaseTestCase;
use PHPUnit\Framework\Assert;

/**
 * @testdox RedisManager
 */
class RedisManagerTest extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Deprecated Test');
    }

    public function testDefaultPoolName(): void
    {
        Assert::assertEquals('redis_test', RedisManager::getDefaultPoolName());
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
            if ($instance)
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
        Assert::assertEquals(1, $pool->getFree());
    }

    private function assertRedisHandler(RedisHandler $redisHandler): void
    {
        Assert::assertInstanceOf(RedisHandler::class, $redisHandler);
        $time = time();
        $redisHandler->set('imi:test:a', $time);
        Assert::assertEquals($time, $redisHandler->get('imi:test:a'));
    }
}
