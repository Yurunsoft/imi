<?php

declare(strict_types=1);

namespace Imi\Redis\Connector;

use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;

class PhpRedisConnector implements IRedisConnector
{
    protected static function checkLibrary(): void
    {
        // 检查redis库是否存在
        if (!\extension_loaded('redis'))
        {
            throw new \RuntimeException('Please install phpredis extension');
        }
        // PHP Redis 5.3.2+ supports SSL/TLS.
        // 最低支持版本考虑提升到 5.3.7
        if (!version_compare(phpversion('redis'), '5.3.2', '>='))
        {
            throw new \RuntimeException('PHP Redis extension version must be 5.3.2+');
        }
    }

    /**
     * 初始化 Redis 连接.
     */
    public static function connect(RedisDriverConfig $config): PhpRedisHandler
    {
        self::checkLibrary();

        // 可以考虑适配 6.0 初始化方式
        $redis = new \Redis();

        $host = $config->host;
        $arguments = [
            $config->host, // host
        ];
        if (str_contains($host, '/'))
        {
            // unix socket
            $arguments[] = 0;
        }
        else
        {
            $arguments[] = $config->port;
        }
        $arguments[] = $config->timeout;
        $arguments[] = null; // reserved
        $arguments[] = 0; // retry_interval
        $arguments[] = $config->readTimeout;

        // extra array, with PhpRedis >= 5.3.0, it allows setting auth and stream configuration.
        $extra = [];
        if ($config->tls)
        {
            $extra['stream'] = $config->tls;
        }
        if ($extra)
        {
            $arguments[] = $extra;
        }

        $redis->connect(...$arguments);

        // todo 需要支持 acl 用户名与密码验证
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
        self::checkLibrary();

        $seeds = $config->seeds;
        $timeout = $config->timeout;
        $readTimeout = $config->readTimeout;
        $password = $config->password;

        $arguments = [
            null, // name
            $seeds,
            $timeout,
            $readTimeout,
            false, // persistent
            $password,
        ];

        if ($config->tls)
        {
            // @link https://github.com/phpredis/phpredis/issues/1600#issuecomment-776109815
            // @link https://github.com/phpredis/phpredis/issues/1607#issuecomment-653578201

            // stream context
            $arguments[] = $config->tls;
        }

        $redis = new \RedisCluster(...$arguments);

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
