<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\PhpRedis;

use Imi\Redis\Test\Tests\Model\AbstractRedisModelHashObject;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PhpRedis/Model/HashObjectModel')]
class PhpRedisModelHashObjectTest extends AbstractRedisModelHashObject
{
    protected string $poolName = 'test_phpredis_standalone';
}
