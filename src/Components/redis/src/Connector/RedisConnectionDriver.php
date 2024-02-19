<?php

declare(strict_types=1);

namespace Imi\Redis\Connector;

use Imi\ConnectionCenter\Contract\AbstractConnectionDriver;
use Imi\ConnectionCenter\Contract\IConnectionConfig;
use Imi\Redis\Enum\RedisMode;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;

class RedisConnectionDriver extends AbstractConnectionDriver
{
    /**
     * @param RedisDriverConfig $config
     *
     * @return object|PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler
     */
    protected function createInstanceByConfig(IConnectionConfig $config): object
    {
        /** @var IRedisConnector $connector */
        $connector = match ($config->client)
        {
            'phpredis' => PhpRedisConnector::class,
            'predis'   => PredisConnector::class,
            default    => throw new \RuntimeException(sprintf('Unsupported redis client: %s', $config->client)),
        };

        return match ($config->mode)
        {
            RedisMode::Standalone => $connector::connect($config),
            RedisMode::Cluster    => $connector::connectCluster($config),
            RedisMode::Sentinel   => throw new \RuntimeException('To be implemented'),
        };
    }

    public static function createConnectionConfig(array|string $config): IConnectionConfig
    {
        return RedisDriverConfig::create($config);
    }

    /**
     * @param PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler $instance
     */
    public function connect(object $instance): object
    {
        return $instance;
    }

    /**
     * @param PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler $instance
     */
    public function close(object $instance): void
    {
    }

    /**
     * @param PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler $instance
     */
    public function reset(object $instance): void
    {
        if (
            !$instance->isCluster()
            && $instance->isConnected()
            && ($db = $instance->getConnectionConfig()->database) !== $instance->getDBNum()
        ) {
            $instance->select($db);
        }
    }

    /**
     * @param PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler $instance
     */
    public function checkAvailable(object $instance): bool
    {
        return $instance->isConnected();
    }

    /**
     * @param PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler $instance
     */
    public function ping(object $instance): bool
    {
        return $instance->isConnected();
    }
}
