<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests;

use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisHandler;
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
     * @phpstan-return PhpRedisHandler
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
     * @phpstan-param PhpRedisHandler $redis
     */
    protected function flush(IRedisHandler $redis): void
    {
        $redis->flushdb(false);
    }

    /**
     * @phpstan-param PhpRedisHandler $redis
     */
    #[Depends('testGetDrive')]
    public function testGetAndSet(IRedisHandler $redis): void
    {
        $str = 'imi niubi!' . bin2hex(random_bytes(4));
        $redis->set('imi:test:a', $str);
        self::assertEquals($str, $redis->get('imi:test:a'));
    }

    /**
     * @phpstan-param PhpRedisHandler $redis
     */
    #[Depends('testGetDrive')]
    public function testEvalEx(IRedisHandler $redis): void
    {
        $value = $redis->evalEx(<<<'SCRIPT'
        local key = KEYS[1]
        local value = ARGV[1]
        redis.call('set', key, value)
        return redis.call('get', key)
        SCRIPT
            , ['imi:test:a', 'imi very 6'], 1);
        self::assertEquals('imi very 6', $value);
    }

    /**
     * @phpstan-param PhpRedisHandler $redis
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
     * @phpstan-param PhpRedisHandler $redis
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
     * @phpstan-param PhpRedisHandler $redis
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
     * @phpstan-param PhpRedisHandler $redis
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
     * @phpstan-param PhpRedisHandler $redis
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
}
