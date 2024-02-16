<?php

declare(strict_types=1);

namespace Imi\Redis;

use Imi\App;
use Imi\Config;
use Imi\ConnectionCenter\Contract\IConnection;
use Imi\ConnectionCenter\Contract\IConnectionManager;
use Imi\ConnectionCenter\Enum\ConnectionStatus;
use Imi\ConnectionCenter\Facade\ConnectionCenter;
use Imi\Pool\PoolManager;
use Imi\Redis\Connector\IRedisConnector;
use Imi\Redis\Connector\PhpRedisConnector;
use Imi\Redis\Connector\PredisConnector;
use Imi\Redis\Enum\RedisMode;
use Imi\Redis\Handler\IRedisHandler;
use Imi\RequestContext;
use Imi\Timer\Timer;

class RedisManager
{
    use \Imi\Util\Traits\TStaticClass;

    private static \WeakMap $instanceLinkConnectionMap;

    /**
     * 获取新的 Redis 连接实例.
     *
     * @param string|null $poolName 连接池名称
     */
    public static function getNewInstance(?string $poolName = null): IRedisHandler
    {
        $poolName = self::parsePoolName($poolName);
        $connection = ConnectionCenter::getConnection($poolName);
        /** @var IRedisHandler $instance */
        $instance = $connection->getInstance();
        self::recordInstanceLinkPool($instance, $connection);
        return $instance;
    }

    /**
     * 获取 Redis 连接实例，每个RequestContext中共用一个.
     *
     * @param string $poolName 连接池名称
     */
    public static function getInstance(?string $poolName = null): IRedisHandler
    {
        $poolName = self::parsePoolName($poolName);
        $connection = ConnectionCenter::getRequestContextConnection($poolName);
        /** @var IRedisHandler $instance */
        $instance = $connection->getInstance();
        self::recordInstanceLinkPool($instance, $connection);
        return $instance;
    }

    /**
     * 使用回调来使用池子中的资源，无需手动释放
     * 回调有两个参数：$connection(连接对象), $instance(操作实例对象，Redis实例)
     * 本方法返回值为回调的返回值
     */
    public static function use(string $name, callable $callback): mixed
    {
        $resource = static::getNewInstance($name);
        try
        {
            $connection = self::getConnectionByInstance($resource);
            return $callback($connection, $resource);
        }
        finally
        {
            static::release($resource);
        }
    }

    protected static function recordInstanceLinkPool(IRedisHandler $handler, IConnection $connection): void
    {
        if (!isset(self::$instanceLinkConnectionMap)) {
            self::$instanceLinkConnectionMap = new \WeakMap();
        }

        self::$instanceLinkConnectionMap[$handler] = $connection;
    }

    protected static function getConnectionByInstance(IRedisHandler $handler): ?IConnection
    {
        return self::$instanceLinkConnectionMap[$handler] ?? null;
    }

    protected static function unsetConnectionInstance(IRedisHandler $handler): void
    {
        unset(self::$instanceLinkConnectionMap[$handler]);
    }

    /**
     * 释放 Redis 连接实例.
     */
    public static function release(IRedisHandler $redis): void
    {
        $connection = self::getConnectionByInstance($redis);
        if (null === $connection) {
            throw new \RuntimeException('RedisHandler is not a valid connection center connection instance');
        }
        if ($connection->getStatus() === ConnectionStatus::WaitRelease) {
            $connection->getManager()->releaseConnection($connection);
        }
        self::unsetConnectionInstance($redis);
    }

    /**
     * 处理连接池 名称.
     */
    public static function parsePoolName(?string $poolName = null): string
    {
        if (null === $poolName || '' === $poolName)
        {
            $poolName = static::getDefaultPoolName();
        }

        return $poolName;
    }

    /**
     * 获取默认池子名称.
     */
    public static function getDefaultPoolName(): string
    {
        return Config::get('@currentServer.redis.defaultPool');
    }

    /**
     * 从当前上下文中获取公用连接
     */
    public static function isQuickFromRequestContext(): bool
    {
        return Config::get('@currentServer.redis.quickFromRequestContext', true);
    }
}
