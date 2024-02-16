<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

interface IRedisHandler
{
    public function getInstance(): object;

    public function isSupportSerialize(): bool;
}
