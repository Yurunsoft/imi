<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

use Imi\Redis\Connector\RedisDriverConfig;

abstract class AbstractRedisHandler implements IRedisScanMethod, IRedisHandler
{
    protected RedisDriverConfig $config;

    abstract public function isConnected(): bool;

    public function isCluster(): bool
    {
        return $this instanceof IRedisClusterHandler;
    }

    public function getConnectionConfig(): RedisDriverConfig
    {
        return $this->config;
    }
}
