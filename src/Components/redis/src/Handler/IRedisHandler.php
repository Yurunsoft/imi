<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

interface IRedisHandler
{
    public function getInstance(): object;

    public function isCluster(): bool;

    public function isSupportSerialize(): bool;

    /**
     * @param mixed $value
     * @return string
     */
    public function _serialize(mixed $value);

    /**
     * @param string $value
     */
    public function _unserialize($value): mixed;
}
