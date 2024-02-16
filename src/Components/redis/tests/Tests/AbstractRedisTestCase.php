<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests;

use Imi\Redis\Handler\IRedisHandler;
use PHPUnit\Framework\TestCase;

/**
 * @template T of IRedisHandler
 */
abstract class AbstractRedisTestCase extends TestCase
{
    public string $driveName = 'test_phpredis_cluster';

    abstract public function testGetDrive(): IRedisHandler;

    abstract protected function flush(IRedisHandler $handler): void;
}
