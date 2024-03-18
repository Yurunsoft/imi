<?php

declare(strict_types=1);

/**
 * @testdox Predis Lock
 */
class LockPredisTest extends \Imi\Test\Component\Tests\BaseLockTestCase
{
    protected ?string $lockConfigId = 'predis';

    protected ?string $lockId = 'imi-predis';
}
