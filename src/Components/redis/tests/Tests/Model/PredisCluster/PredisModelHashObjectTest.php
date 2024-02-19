<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Model\PredisCluster;

use Imi\Redis\Test\Tests\Model\AbstractRedisModelHashObject;
use Imi\Util\Format\PhpSerialize;
use PHPUnit\Framework\Attributes\TestDox;

#[TestDox('Redis/PredisCluster/Model/HashObjectModel')]
class PredisModelHashObjectTest extends AbstractRedisModelHashObject
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
