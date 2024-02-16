<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\PhpRedis;

use Imi\Redis\Test\Tests\Model\AbstractRedisModel;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PhpRedis/Model/BaseModel')]
class PhpRedisModelTest extends AbstractRedisModel
{
    protected string $poolName = 'test_phpredis_standalone';
}
