<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests;

use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PredisHandler;
use Imi\Redis\RedisManager;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestDox;
use Predis\Client;

/**
 * @implements PhpRedisTest<PredisHandler>
 */
#[TestDox('Redis/Predis/Standalone')]
class PredisTest extends PhpRedisTest
{
    public string $driveName = 'test_predis_standalone';

    public function testGetDrive(): IRedisHandler
    {
        $redisClient = RedisManager::getInstance($this->driveName);
        self::assertInstanceOf(PredisHandler::class, $redisClient);
        self::assertInstanceOf(Client::class, $redisClient->getInstance());

        return $redisClient;
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
}
