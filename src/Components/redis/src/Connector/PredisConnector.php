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
        $params = [
            'scheme' => 'tcp',
            'host'   => $config->host,
            'port'   => $config->port,
        ];
        if ($config->password)
        {
            $params['password'] = $config->password;
        }
        if (null !== $config->database)
        {
            $params['database'] = $config->database;
        }
        if ($config->prefix)
        {
            $params['prefix'] = $config->prefix;
        }
        $client = new \Predis\Client($params);
        $client->connect();

        return new PredisHandler($client);
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

        return new PredisClusterHandler($client);
    }
}
