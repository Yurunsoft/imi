<?php

declare(strict_types=1);

namespace Imi\Redis;

use Imi\Redis\Handler\IRedisHandler;

interface IRedisLuaScript
{
    public function keysNum(): int;

    public function script(): string;

    public function withReadOnly(): static;

    public function invoke(IRedisHandler $redis, array $keys, ...$argv): mixed;
}
