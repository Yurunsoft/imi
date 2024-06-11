<?php

declare(strict_types=1);

namespace Imi\Redis\Connector;

use Imi\Redis\Handler\IRedisClusterHandler;
use Imi\Redis\Handler\IRedisHandler;

interface IRedisConnector
{
    public static function connect(RedisDriverConfig $config): IRedisHandler;

    public static function connectCluster(RedisDriverConfig $config): IRedisClusterHandler;
}
