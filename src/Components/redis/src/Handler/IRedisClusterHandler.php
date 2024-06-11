<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

interface IRedisClusterHandler extends IRedisHandler
{
    /**
     * @return array<array{0: string, 1: int}>
     */
    public function getNodes(): array;
}
