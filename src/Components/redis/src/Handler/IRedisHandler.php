<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

interface IRedisHandler
{
    public function getInstance(): object;

    public function isCluster(): bool;

    public function isSupportSerialize(): bool;

    public function _serialize(mixed $value): ?string;

    public function _unserialize(?string $value): mixed;
}
