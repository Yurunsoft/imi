<?php

declare(strict_types=1);

namespace Imi\Redis\Test\Tests\Classes;

use Imi\Redis\RedisLuaScript;

class TestEvalScript1 extends RedisLuaScript
{
    private bool $triggerError = false;

    public function keysNum(): int
    {
        return 1;
    }

    public function script(): string
    {
        $errorScript = $this->triggerError ? "local num = 'e'*1" : '';

        return <<<LUA
        local key = KEYS[1]
        local value = ARGV[1]
        {$errorScript}
        redis.call('set', key, value)
        return redis.call('get', key)
        LUA;
    }

    public function triggerScriptError(): static
    {
        $script = clone $this;
        $script->triggerError = true;
        $script->reset();

        return $script;
    }
}
