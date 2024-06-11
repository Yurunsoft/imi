<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Classes;

use Imi\Redis\RedisLuaScript;

class TestEvalScriptReadOnly extends RedisLuaScript
{
    public function keysNum(): int
    {
        return 2;
    }

    public function script(): string
    {
        return <<<'LUA'
        local key = KEYS[1]
        local value = ARGV[1]
        return redis.call('GET', key) .. '_' .. value
        LUA;
    }
}
