<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\Predis;

use Imi\Redis\Test\Tests\Model\AbstractRedisModelHash;
use Imi\Util\Format\PhpSerialize;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/Predis/Model/HashModel')]
class PredisModelHashTest extends AbstractRedisModelHash
{
    protected string $poolName = 'test_predis_standalone';
    protected ?string $formatter = PhpSerialize::class;
}
