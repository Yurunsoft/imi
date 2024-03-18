<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Classes;

use Imi\Bean\Annotation\Bean;
use Imi\Redis\Annotation\RedisInject;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisHandler;
use PHPUnit\Framework\Assert;

#[Bean(name: 'TestInjectRedisHandler')]
class TestInjectRedis
{
    #[RedisInject('test_phpredis_standalone')]
    protected PhpRedisHandler $phpredis;

    #[RedisInject('test_predis_standalone')]
    protected PredisHandler $predis;

    public function testPhpRedis(): void
    {
        Assert::assertInstanceOf(PhpRedisHandler::class, $this->phpredis);
        $time = time();
        $this->phpredis->set('imi:test:a1', $time);
        Assert::assertEquals($time, $this->phpredis->get('imi:test:a1'));
    }

    public function testPredis(): void
    {
        Assert::assertInstanceOf(PredisHandler::class, $this->predis);
        $time = time();
        $this->phpredis->set('imi:test:a2', $time);
        Assert::assertEquals($time, $this->phpredis->get('imi:test:a2'));
    }
}
