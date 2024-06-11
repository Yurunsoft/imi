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
 * @template T of PredisClusterHandler
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

    /**
     * @phpstan-return T
     */
    public function testGetDrive(): IRedisHandler
    {
        $redisClient = RedisManager::getInstance($this->driveName);
        self::assertInstanceOf(PredisClusterHandler::class, $redisClient);
        self::assertInstanceOf(Client::class, $redisClient->getInstance());

        self::assertTrue($redisClient->flushdbEx());

        return $redisClient;
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testGeoAdd(IRedisHandler $redis): void
    {
        if (\PHP_OS_FAMILY === 'Windows')
        {
            self::markTestSkipped('Windows redis not support geo.');
        }

        self::assertEquals(1, $redis->geoadd('imi:geo', 120.31858, 31.49881, 'value_' . bin2hex(random_bytes(4))));
    }

    /**
     * @phpstan-param T $redis
     */
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

    /**
     * @phpstan-param T $redis
     */
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
