<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests;

use Imi\Config;
use Imi\Redis\Handler\AbstractRedisHandler;
use Imi\Redis\Handler\IRedisClusterHandler;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Redis;
use Imi\Redis\RedisManager;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * @template T of PhpRedisHandler
 */
#[TestDox('Redis/PhpRedis/Standalone')]
class PhpRedisTest extends TestCase
{
    public string $driveName = 'test_phpredis_standalone';

    /**
     * @phpstan-return T
     */
    public function testGetDrive(): IRedisHandler
    {
        $redisClient = RedisManager::getInstance($this->driveName);
        self::assertInstanceOf(PhpRedisHandler::class, $redisClient);
        self::assertInstanceOf(\Redis::class, $redisClient->getInstance());

        // 清空数据
        $this->flush($redisClient);

        return $redisClient;
    }

    /**
     * @phpstan-param T $redis
     */
    protected function flush(IRedisHandler $redis): void
    {
        $redis->flushdb(false);
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testGetAndSet(IRedisHandler $redis): void
    {
        $str = 'imi niubi!' . bin2hex(random_bytes(4));
        $redis->set('imi:test:a', $str);
        self::assertEquals($str, $redis->get('imi:test:a'));
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testEvalEx(IRedisHandler $redis): void
    {
        $value = $redis->evalEx(
            <<<'SCRIPT'
            local key = KEYS[1]
            local value = ARGV[1]
            redis.call('set', key, value)
            return redis.call('get', key)
            SCRIPT,
            ['imi:test:a', 'imi very 6'],
            1,
        );
        self::assertEquals('imi very 6', $value);
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testScanEach(IRedisHandler $redis): void
    {
        $excepted = $map = [];
        for ($i = 0; $i < 100; ++$i)
        {
            $key = 'imi:scanEach:' . $i;
            $excepted[$key] = 1;
            $map[$key] = 0;
            $redis->set($key, $i);
        }
        foreach ($redis->scanEach('imi:scanEach:*', 10) as $value)
        {
            $map[$value] = 1;
        }
        self::assertEquals($excepted, $map);
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testHscanEach(IRedisHandler $redis): void
    {
        $excepted = $map = $values = $exceptedValues = [];
        $key = 'imi:hscanEach';
        $redis->del($key);
        for ($i = 0; $i < 100; ++$i)
        {
            $member = 'value:' . $i;
            $excepted[$member] = 1;
            $map[$member] = 0;
            $values[$member] = -1;
            $exceptedValues[$member] = $i;
            $redis->hSet($key, $member, $i);
        }
        foreach ($redis->hscanEach($key, 'value:*', 10) as $k => $value)
        {
            $map[$k] = 1;
            $values[$k] = $value;
        }
        self::assertEquals($excepted, $map);
        self::assertEquals($exceptedValues, $values);
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testSscanEach(IRedisHandler $redis): void
    {
        $excepted = $map = [];
        $key = 'imi:sscanEach';
        $redis->del($key);
        for ($i = 0; $i < 100; ++$i)
        {
            $value = 'value:' . $i;
            $excepted[$value] = 1;
            $map[$value] = 0;
            $redis->sAdd($key, $value);
        }
        foreach ($redis->sscanEach($key, '*', 10) as $value)
        {
            $map[$value] = 1;
        }
        self::assertEquals($excepted, $map);
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testZscanEach(IRedisHandler $redis): void
    {
        $excepted = $map = [];
        $key = 'imi:zscanEach';
        $redis->del($key);
        for ($i = 0; $i < 100; ++$i)
        {
            $value = 'value:' . $i;
            $excepted[$i] = 1;
            $map[$i] = 0;
            $redis->zAdd($key, $i, $value);
        }
        foreach ($redis->zscanEach($key, '*', 10) as $score)
        {
            $map[$score] = 1;
        }
        self::assertEquals($excepted, $map);
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
        $oriOption = $redis->getOption(\Redis::OPT_SERIALIZER);

        self::assertTrue($redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP));
        self::assertEquals(1, $redis->geoAdd('imi:geo', 120.31858, 31.49881, 'value_' . bin2hex(random_bytes(4))));

        self::assertTrue($redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE));
        self::assertEquals(1, $redis->geoAdd('imi:geo', 120.31858, 31.49881, 'value_' . bin2hex(random_bytes(4))));

        $redis->setOption(\Redis::OPT_SERIALIZER, $oriOption);
    }

    protected function staticContextWarp(callable $fn): mixed
    {
        $defaultName = RedisManager::getDefaultPoolName();
        self::assertTrue(Config::set('@app.redis.defaultPool', $this->driveName));
        self::assertEquals($this->driveName, RedisManager::getDefaultPoolName());
        try
        {
            return $fn();
        }
        finally
        {
            Config::set('@app.redis.defaultPool', $defaultName);
        }
    }

    #[Depends('testGetDrive')]
    public function testStaticCall(): void
    {
        $prefix = __FUNCTION__ . bin2hex(random_bytes(4));
        $this->staticContextWarp(static function () use ($prefix): void {
            Redis::set($prefix, '123456');
            self::assertEquals('123456', Redis::get($prefix));
            self::assertTrue(Redis::del($prefix) > 0);
        });
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testStaticCallScan(IRedisHandler $redis): void
    {
        if ($redis instanceof IRedisClusterHandler)
        {
            self::markTestSkipped('RedisClusterHandler does not support hscan');
        }

        $prefix = __FUNCTION__ . bin2hex(random_bytes(4));
        $this->staticContextWarp(static function () use ($redis, $prefix): void {
            $excepted = $map = [];
            for ($i = 0; $i < 100; ++$i)
            {
                $key = $prefix . ':scanEach:' . $i;
                $excepted[$key] = 1;
                $map[$key] = 0;
                $redis->set($key, $i);
            }

            $map = [];
            do
            {
                $keys = Redis::scan($it, $prefix . ':scanEach:*', 10);
                foreach ($keys as $key)
                {
                    $map[$key] = 1;
                }
            }
            while (0 != $it);
            self::assertEquals($excepted, $map);
            self::assertTrue(Redis::del(array_keys($excepted)) > 0);
        });
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testStaticCallHScan(IRedisHandler $redis): void
    {
        if ($redis instanceof IRedisClusterHandler)
        {
            self::markTestSkipped('RedisClusterHandler does not support hscan');
        }

        $prefix = __FUNCTION__ . bin2hex(random_bytes(4));
        $this->staticContextWarp(static function () use ($redis, $prefix): void {
            $excepted = $map = $values = $exceptedValues = [];
            $key = $prefix . ':hscanEach';
            $redis->del($key);
            for ($i = 0; $i < 100; ++$i)
            {
                $member = 'value:' . $i;
                $excepted[$member] = 1;
                $map[$member] = 0;
                $values[$member] = -1;
                $exceptedValues[$member] = $i;
                $redis->hSet($key, $member, $i);
            }
            do
            {
                $items = Redis::hscan($key, $it, 'value:*', 10);
                foreach ($items as $k => $value)
                {
                    $map[$k] = 1;
                    $values[$k] = $value;
                }
            }
            while ($it > 0);
            self::assertEquals($excepted, $map);
            self::assertEquals($exceptedValues, $values);
            self::assertTrue(Redis::del($key) > 0);
        });
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testStaticCallSScan(IRedisHandler $redis): void
    {
        if ($redis instanceof IRedisClusterHandler)
        {
            self::markTestSkipped('RedisClusterHandler does not support sscan');
        }

        $prefix = __FUNCTION__ . bin2hex(random_bytes(4));
        $this->staticContextWarp(static function () use ($redis, $prefix): void {
            $excepted = $map = [];
            $key = $prefix . ':sscanEach';
            $redis->del($key);
            for ($i = 0; $i < 100; ++$i)
            {
                $value = 'value:' . $i;
                $excepted[$value] = 1;
                $map[$value] = 0;
                $redis->sAdd($key, $value);
            }
            do
            {
                $items = Redis::sscan($key, $it, '*', 10);
                foreach ($items as $value)
                {
                    $map[$value] = 1;
                }
            }
            while ($it > 0);
            self::assertEquals($excepted, $map);
            self::assertTrue(Redis::del($key) > 0);
        });
    }

    /**
     * @phpstan-param T $redis
     */
    #[Depends('testGetDrive')]
    public function testStaticCallZScan(IRedisHandler $redis): void
    {
        if ($redis instanceof IRedisClusterHandler)
        {
            self::markTestSkipped('RedisClusterHandler does not support zscan');
        }

        $prefix = __FUNCTION__ . bin2hex(random_bytes(4));
        $this->staticContextWarp(static function () use ($redis, $prefix): void {
            $excepted = $map = [];
            $key = $prefix . ':zscanEach';
            $redis->del($key);
            for ($i = 0; $i < 100; ++$i)
            {
                $value = 'value:' . $i;
                $excepted[$i] = 1;
                $map[$i] = 0;
                $redis->zAdd($key, $i, $value);
            }
            do
            {
                $items = Redis::zscan($key, $it, '*', 10);
                foreach ($items as $score)
                {
                    $map[$score] = 1;
                }
            }
            while ($it > 0);
            self::assertEquals($excepted, $map);
            self::assertTrue(Redis::del($key) > 0);
        });
    }

    #[Depends('testGetDrive')]
    public function testStaticCallEval(): void
    {
        $prefix = __FUNCTION__ . bin2hex(random_bytes(4));
        $value = $this->staticContextWarp(static function () use ($prefix): mixed {
            return Redis::evalEx(
                <<<'SCRIPT'
                    local key = KEYS[1]
                    local value = ARGV[1]
                    redis.call('set', key, value)
                    return redis.call('get', key)
                    SCRIPT,
                [$prefix . 'imi:test:a', 'imi very 6'],
                1,
            );
        });
        self::assertEquals('imi very 6', $value);
    }
}
