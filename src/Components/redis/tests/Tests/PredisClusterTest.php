<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests;

use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\RedisManager;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestDox;
use Predis\Client;

/**
 * @implements PhpRedisTest<PredisClusterHandler>
 */
#[TestDox('Redis/Predis/Cluster')]
class PredisClusterTest extends PhpRedisTest
{
    public string $driveName = 'test_predis_cluster';

    public static function setUpBeforeClass(): void
    {
        if (\PHP_OS_FAMILY !== 'Linux')
        {
            self::markTestSkipped('not support redis cluster');
        }
    }

    public function testGetDrive(): IRedisHandler
    {
        $redisClient = RedisManager::getInstance($this->driveName);
        self::assertInstanceOf(PredisClusterHandler::class, $redisClient);
        self::assertInstanceOf(Client::class, $redisClient->getInstance());

        $this->flush($redisClient);

        return $redisClient;
    }

    protected function flush(IRedisHandler $redis): void
    {
        // 清空数据
        foreach ($redis->getNodes() as $node)
        {
            $client = $redis->getClientBy('id', "{$node[0]}:{$node[1]}");
            $result = $client->flushdb();
            self::assertTrue($result instanceof \Predis\Response\Status && 'OK' === (string) $result);
        }
    }

    #[Depends('testGetDrive')]
    public function testGeoAdd(IRedisHandler $redis): void
    {
        if (\PHP_OS_FAMILY === 'Windows')
        {
            self::markTestSkipped('Windows redis not support geo.');
        }

        self::assertEquals(1, $redis->geoAdd('imi:geo', 120.31858, 31.49881, 'value_' . bin2hex(random_bytes(4))));
    }

    #[Depends('testGetDrive')]
    public function testHashKeysSlot(IRedisHandler $redis): void
    {
        /** @var PredisClusterHandler $redis */
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

        $keysBySlot = $redis->getSlotGroupByKeys($keys);

        foreach ($keysBySlot as $items)
        {
            self::assertEquals(\count($items), $redis->del($items), 'del failed');
        }
    }

    #[Depends('testGetDrive')]
    public function testHashKeysTags(IRedisHandler $redis): void
    {
        /** @var PredisClusterHandler $redis */
        $prefix = '{imi:hash-test}:k';
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

        $keys = array_keys($groupItems);
        self::assertEquals(\count($keys), $redis->del($keys), 'del failed');
    }
}
