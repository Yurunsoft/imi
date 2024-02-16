<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\Predis;

use Imi\Redis\Test\Tests\Model\AbstractRedisModel;
use Imi\Util\Format\PhpSerialize;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PhpRedis/Model/BaseModel')]
class PredisModel extends AbstractRedisModel
{
    protected string $poolName = 'test_predis_standalone';
    protected ?string $formatter = PhpSerialize::class;
}
