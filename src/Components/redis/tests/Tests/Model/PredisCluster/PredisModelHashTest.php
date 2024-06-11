<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\PredisCluster;

use Imi\Redis\Test\Tests\Model\AbstractRedisModelHash;
use Imi\Util\Format\PhpSerialize;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PredisCluster/Model/HashModel')]
class PredisModelHashTest extends AbstractRedisModelHash
{
    protected string $poolName = 'test_predis_cluster';
    protected ?string $formatter = PhpSerialize::class;

    public static function setUpBeforeClass(): void
    {
        if (\PHP_OS_FAMILY !== 'Linux')
        {
            self::markTestSkipped('not support redis cluster');
        }
    }
}
