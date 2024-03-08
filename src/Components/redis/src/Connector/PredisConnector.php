<?php

declare(strict_types=1);

namespace Imi\Redis\Connector;

use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;

class PredisConnector implements IRedisConnector
{
    /**
     * 初始化 Redis 连接.
     */
    public static function connect(RedisDriverConfig $config): PredisHandler
    {
        // tcp, unix, tls, redis, rediss, http
        $params = [
            'scheme'   => $config->scheme ?? 'tcp',
            'database' => $config->database,
        ];
        if ('unix' === $config->scheme)
        {
            $params['path'] = $config->host;
        }
        else
        {
            $params['host'] = $config->host;
            $params['port'] = $config->port;
        }
        if ($config->password)
        {
            $params['password'] = $config->password;
        }
        if ($config->timeout > 0.0)
        {
            $params['timeout'] = $config->timeout;
        }
        if ($config->readTimeout > 0.0)
        {
            $params['read_write_timeout'] = $config->readTimeout;
        }
        if ($config->prefix)
        {
            $params['prefix'] = $config->prefix;
        }
        if ($config->tls)
        {
            $params['scheme'] = 'tls';
            $params['ssl'] = $config->tls;
        }
        $client = new \Predis\Client($params);
        $client->connect();

        return new PredisHandler($client, $config);
    }

    public static function connectCluster(RedisDriverConfig $config): PredisClusterHandler
    {
        $seeds = $config->seeds ?? [];
        $options['cluster'] = 'redis';

        if ($config->password)
        {
            $options['parameters']['password'] = $config->password;
        }
        if (null !== $config->database)
        {
            $options['parameters']['database'] = $config->database;
        }
        if ($config->prefix)
        {
            $options['prefix'] = $config->prefix;
        }

        $client = new \Predis\Client($seeds, $options);
        $client->connect();

        return new PredisClusterHandler($client, $config);
    }
}
