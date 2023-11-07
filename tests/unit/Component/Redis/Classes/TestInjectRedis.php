<?php

declare(strict_types=1);

namespace Imi\Test\Component\Redis\Classes;

use Imi\Bean\Annotation\Bean;
use Imi\Redis\Annotation\RedisInject;
use Imi\Redis\RedisHandler;
use PHPUnit\Framework\Assert;

#[Bean(name: 'TestInjectRedis')]
class TestInjectRedis
{
    #[RedisInject]
    protected RedisHandler $redis;

    public function test(): void
    {
        Assert::assertInstanceOf(RedisHandler::class, $this->redis);
        $time = time();
        $this->redis->set('imi:test:a', $time);
        Assert::assertEquals($time, $this->redis->get('imi:test:a'));
    }
}
