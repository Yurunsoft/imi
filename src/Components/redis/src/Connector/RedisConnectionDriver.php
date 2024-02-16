<?php
declare(strict_types=1);

namespace Imi\Redis\Connector;

use Imi\ConnectionCenter\Contract\AbstractConnectionDriver;
use Imi\ConnectionCenter\Contract\IConnectionConfig;
use Imi\Redis\Enum\RedisMode;
use Imi\Redis\Handler\IRedisHandler;

class RedisConnectionDriver extends AbstractConnectionDriver
{

    /**
     * @param RedisDriverConfig $config
     * @return object|\Imi\Redis\Handler\IRedisClusterHandler|\Imi\Redis\Handler\IRedisHandler
     */
    protected function createInstanceByConfig(IConnectionConfig $config): object
    {
        /** @var IRedisConnector $connector */
        $connector = match ($config->client)
        {
            'phpredis' => PhpRedisConnector::class,
            'predis'   => PredisConnector::class,
        };

        return match ($config->mode)
        {
            RedisMode::Standalone => $connector::connect($config),
            RedisMode::Cluster    => $connector::connectCluster($config),
            RedisMode::Sentinel => throw new \RuntimeException('To be implemented'),
        };
    }

    public static function createConnectionConfig(array|string $config): IConnectionConfig
    {
        return RedisDriverConfig::create($config);
    }

    /**
     * @param IRedisHandler $instance
     */
    public function connect(object $instance): object
    {
        return $instance;
    }

    /**
     * @param IRedisHandler $instance
     */
    public function close(object $instance): void
    {
    }

    /**
     * @param IRedisHandler $instance
     */
    public function reset(object $instance): void
    {
    }

    /**
     * @param IRedisHandler $instance
     */
    public function checkAvailable(object $instance): bool
    {
        return $instance->isConnected();
    }

    /**
     * @param IRedisHandler $instance
     */
    public function ping(object $instance): bool
    {
        return $instance->isConnected();
    }
}
