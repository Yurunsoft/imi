<?php

declare(strict_types=1);

namespace Imi\Redis;

use Imi\ConnectionCenter\Contract\IConnection;
use Imi\ConnectionCenter\Facade\ConnectionCenter;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;

class RedisConnectionService
{
    public function __construct(private ?string $poolName = null)
    {
    }

    public function setPoolName(?string $poolName): void
    {
        if (null !== $this->poolName && $this->poolName !== $poolName)
        {
            throw new \RuntimeException('Redis pool name is already set');
        }

        $this->poolName = $poolName;
    }

    private function getPoolName(): string
    {
        return RedisManager::parsePoolName($this->poolName);
    }

    /**
     * 获取新的 Redis 连接实例.
     *
     * @return PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler
     */
    public function getNewInstance(): IRedisHandler
    {
        $poolName = $this->getPoolName();
        $manager = ConnectionCenter::getConnectionManager($poolName);

        return $manager->getDriver()->createInstance();
    }

    /**
     * 获取 Redis 客户实例，每个RequestContext中共用一个.
     *
     * @return PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler
     */
    public function getInstance(): IRedisHandler
    {
        $poolName = $this->getPoolName();
        $connection = ConnectionCenter::getRequestContextConnection($poolName);

        return $connection->getInstance();
    }

    /**
     * 使用回调来使用池子中的资源，无需手动释放
     * 回调有两个参数：$connection(连接对象), $instance(操作实例对象，Redis实例)
     * 本方法返回值为回调的返回值
     *
     * @param callable(IConnection, IRedisHandler|object): mixed $callable
     */
    public function use(callable $callable): mixed
    {
        $poolName = $this->getPoolName();

        // 是否需要继续保留这个调用方式？
        // 可能能在回调生命周期内持续持有 $connection，避免被提前释放掉？？

        /** @var IConnection<PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler> $connection */
        if (ConnectionCenter::hasConnectionManager($poolName))
        {
            $connection = ConnectionCenter::getConnection($poolName);

            return $callable($connection, $connection->getInstance());
        }
        else
        {
            // 逻辑上是死代码，hasConnectionManager 肯定是 true
            $connection = ConnectionCenter::getRequestContextConnection($poolName);

            return $callable($connection, $connection->getInstance());
        }
    }
}
