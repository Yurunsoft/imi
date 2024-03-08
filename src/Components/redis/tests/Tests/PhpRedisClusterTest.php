<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests;

use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\RedisManager;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * @template T of PhpRedisClusterHandler
 */
#[TestDox('Redis/PhpRedis/Cluster')]
class PhpRedisClusterTest extends PhpRedisTest
{
    public string $driveName = 'test_phpredis_cluster';

    public static function setUpBeforeClass(): void
    {
        if (\PHP_OS_FAMILY !== 'Linux')
        {
            self::markTestSkipped('not support redis cluster');
        }
    }

    /**
     * @phpstan-return T
     */
    public function testGetDrive(): IRedisHandler
    {
        $redisClient = RedisManager::getInstance($this->driveName);
        self::assertInstanceOf(PhpRedisClusterHandler::class, $redisClient);
        self::assertInstanceOf(\RedisCluster::class, $redisClient->getInstance());

        $this->flush($redisClient);

        return $redisClient;
    }

    /**
     * @phpstan-param T $redis
     */
    protected function flush(IRedisHandler $redis): void
    {
        // 清空数据
        foreach ($redis->getNodes() as $node)
        {
            self::assertTrue($redis->flushdb($node, false));
        }
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testHashKeys(IRedisHandler $redis): void
    {
        $prefix = 'imi:hash-test:k';

        $groupItems = [];
        for ($i = 0; $i < 100; ++$i)
        {
            $key = $prefix . dechex($i);
            $groupItems[$key] = [];
            for ($ii = 0; $ii < 100; ++$ii)
            {
                $groupItems[$key]["hk_{$ii}"] = 'hv_' . dechex($ii);
            }
        }
        foreach ($groupItems as $k => $items)
        {
            self::assertTrue($redis->hmset($k, $items), "hmset {$k} failed");
        }

        foreach ($groupItems as $k => $items)
        {
            $result = $redis->hgetall($k);
            self::assertEquals($items, $result, "hgetall {$k} failed");
        }

        $hasKeys = [];
        foreach ($redis->scanEach($prefix . '*', 20) as $key)
        {
            $hasKeys[] = $key;
        }

        $keys = array_keys($groupItems);
        self::assertEquals($keys, array_intersect($keys, $hasKeys), 'scanEach failed');

        self::assertEquals(\count($keys), $redis->del($keys), 'del failed');
    }
}
