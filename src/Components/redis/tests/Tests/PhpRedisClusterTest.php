<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests;

use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\RedisManager;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * @implements PhpRedisTest<PhpRedisClusterHandler>
 */
#[TestDox('Redis/PhpRedis/Cluster')]
class PhpRedisClusterTest extends PhpRedisTest
{
    public string $driveName = 'test_phpredis_cluster';

    public function testGetDrive(): IRedisHandler
    {
        $redisClient = RedisManager::getInstance($this->driveName);
        self::assertInstanceOf(PhpRedisClusterHandler::class, $redisClient);
        self::assertInstanceOf(\RedisCluster::class, $redisClient->getInstance());

        $this->flush($redisClient);

        return $redisClient;
    }

    protected function flush(IRedisHandler $redis): void
    {
        // 清空数据
        foreach ($redis->getNodes() as $node) {
            self::assertTrue($redis->flushdb($node, false));
        }
    }
}
