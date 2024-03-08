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
    public string $driveName = '';

    /**
     * @phpstan-return T
     */
    abstract public function testGetDrive(): IRedisHandler;

    /**
     * @phpstan-param T $redis
     */
    abstract protected function flush(IRedisHandler $redis): void;
}
