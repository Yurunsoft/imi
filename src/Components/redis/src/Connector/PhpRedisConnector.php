<?php

declare(strict_types=1);

namespace Imi\Redis\Connector;

use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;

class PhpRedisConnector implements IRedisConnector
{
    /**
     * 初始化 Redis 连接.
     */
    public static function connect(RedisDriverConfig $config): PhpRedisHandler
    {
        $redis = new \Redis();

        $host = $config->host;
        if (str_contains($host, '/'))
        {
            // unix socket
            $redis->connect($host);
        }
        else
        {
            $redis->connect($host, $config->port, $config->timeout);
        }
        if (('' !== $config->password) && !$redis->auth($config->password) && null !== $redis->getLastError())
        {
            throw new \RedisException($redis->getLastError());
        }
        if (!$redis->select($config->database) && null !== $redis->getLastError())
        {
            throw new \RedisException($redis->getLastError());
        }

        self::applyOptions($redis, $config);

        return new PhpRedisHandler($redis, $config);
    }

    public static function connectCluster(RedisDriverConfig $config): PhpRedisClusterHandler
    {
        $seeds = $config->seeds;
        $timeout = $config->timeout;
        $readTimeout = $config->readTimeout;
        $password = $config->password;

        $redis = new \RedisCluster(null, $seeds, $timeout, $readTimeout, false, $password);

        self::applyOptions($redis, $config);

        return new PhpRedisClusterHandler($redis, $config);
    }

    protected static function applyOptions(\Redis|\RedisCluster $redis, RedisDriverConfig $config): void
    {
        $options = $config->options ?? [];
        // 兼容序列化定义
        if ($config->serialize && !isset($options[\Redis::OPT_SERIALIZER]))
        {
            $options[\Redis::OPT_SERIALIZER] = \Redis::SERIALIZER_PHP;
        }
        // 设置自定义选项
        if ($options)
        {
            foreach ($options as $key => $value)
            {
                if (!$redis->setOption($key, $value))
                {
                    throw new \RuntimeException(sprintf('Redis setOption %s=%s failed', $key, $value));
                }
            }
        }
    }
}
