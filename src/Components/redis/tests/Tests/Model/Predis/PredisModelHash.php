<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\Predis;

use Imi\Redis\Test\Tests\Model\AbstractRedisModelHash;
use Imi\Util\Format\PhpSerialize;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PhpRedis/Model/HashModel')]
class PredisModelHash extends AbstractRedisModelHash
{
    protected string $poolName = 'test_predis_standalone';
    protected ?string $formatter = PhpSerialize::class;
}
