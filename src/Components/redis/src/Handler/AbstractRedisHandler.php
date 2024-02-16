<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

abstract class AbstractRedisHandler implements IRedisScanExMethod
{
    abstract public function isConnected(): bool;

    public function isCluster(): bool
    {
        return $this instanceof IRedisClusterHandler;
    }
}
