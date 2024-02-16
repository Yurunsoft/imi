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
        foreach ($redis->getNodes() as $node) {
            $client = $redis->getClientBy('id', "{$node[0]}:{$node[1]}");
            self::assertTrue($client->flushdb());
        }
    }

    #[Depends('testGetDrive')]
    public function testGeoAdd(IRedisHandler $redis): void
    {
        if (\PHP_OS_FAMILY === 'Windows')
        {
            self::markTestSkipped('Windows redis not support geo.');
        }

        self::assertEquals(1, $redis->geoAdd('imi:geo', 120.31858, 31.49881, 'value_' . \bin2hex(\random_bytes(4))));
    }
}
