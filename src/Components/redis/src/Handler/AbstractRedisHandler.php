<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

use Imi\Redis\Connector\RedisDriverConfig;

abstract class AbstractRedisHandler implements IRedisHandler
{
    protected RedisDriverConfig $config;

    public function isCluster(): bool
    {
        return $this instanceof IRedisClusterHandler;
    }

    public function getConnectionConfig(): RedisDriverConfig
    {
        return $this->config;
    }
}
