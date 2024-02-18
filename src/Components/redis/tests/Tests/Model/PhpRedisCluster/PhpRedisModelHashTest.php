<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\PhpRedisCluster;

use Imi\Redis\Test\Tests\Model\AbstractRedisModelHash;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PhpRedis/Cluster/Model/HashModel')]
class PhpRedisModelHashTest extends AbstractRedisModelHash
{
    protected string $poolName = 'test_phpredis_cluster';

    public static function setUpBeforeClass(): void
    {
        if (\PHP_OS_FAMILY !== 'Linux')
        {
            self::markTestSkipped('not support redis cluster');
        }
    }
}
