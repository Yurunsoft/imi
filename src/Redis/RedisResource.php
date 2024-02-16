<?php

declare(strict_types=1);

namespace Imi\Redis;

use Imi\Pool\BasePoolResource;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;

/**
 * @deprecated
 */
class RedisResource extends BasePoolResource
{
    /**
     * 连接配置.
     */
    private array $config = [];

    public function __construct(
        \Imi\Pool\Interfaces\IPool $pool,
        /**
         * Redis 对象
         *
         * @var IRedisHandler|PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler|null
         */
        private ?IRedisHandler $redis,
        array $config
    ) {
        parent::__construct($pool);

        if (isset($config['timeout']))
        {
            $config['timeout'] = (float) $config['timeout'];
        }
        if (isset($config['db']))
        {
            $config['db'] = (int) $config['db'];
        }
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function open(): bool
    {
        RedisManager::initRedisConnection($this->redis, $this->config);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        $this->redis->close();
    }

    /**
     * {@inheritDoc}
     */
    public function getInstance(): mixed
    {
        return $this->redis;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $config = $this->config;
        $redis = $this->redis;
        if (!$redis->isCluster() && $redis->isConnected() && ($db = $config['db'] ?? 0) !== $redis->getDBNum())
        {
            $redis->select($db);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkState(): bool
    {
        return $this->redis->isConnected();
    }

    /**
     * {@inheritDoc}
     */
    public function isOpened(): bool
    {
        $redis = $this->redis;

        return $redis->isConnected();
    }
}
