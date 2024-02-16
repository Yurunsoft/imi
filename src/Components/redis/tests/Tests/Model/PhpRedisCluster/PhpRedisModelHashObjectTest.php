<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\PhpRedisCluster;

use Imi\Redis\Test\Tests\Model\AbstractRedisModelHashObject;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PhpRedis/Cluster/Model/HashObjectModel')]
class PhpRedisModelHashObjectTest extends AbstractRedisModelHashObject
{
    protected string $poolName = 'test_phpredis_cluster';
}
