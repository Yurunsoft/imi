<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\PhpRedis;

use Imi\Redis\Test\Tests\Model\AbstractRedisModelHash;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PhpRedis/Model/HashModel')]
class PhpRedisModelHashTest extends AbstractRedisModelHash
{
    protected string $poolName = 'test_phpredis_standalone';
}
