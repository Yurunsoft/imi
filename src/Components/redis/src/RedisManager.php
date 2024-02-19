<?php

declare(strict_types=1);

namespace Imi\Redis;

use Imi\Config;
use Imi\ConnectionCenter\Facade\ConnectionCenter;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;

class RedisManager
{
    use \Imi\Util\Traits\TStaticClass;

    /**
     * 获取新的 Redis 连接实例.
     *
     * @param string|null $poolName 连接池名称
     *
     * @return PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler
     */
    public static function getNewInstance(?string $poolName = null): IRedisHandler
    {
        $poolName = self::parsePoolName($poolName);
        $manager = ConnectionCenter::getConnectionManager($poolName);

        return $manager->getDriver()->createInstance();
    }

    /**
     * 获取 Redis 连接实例，每个RequestContext中共用一个.
     *
     * @param string $poolName 连接池名称
     *
     * @return PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler
     */
    public static function getInstance(?string $poolName = null): IRedisHandler
    {
        $poolName = self::parsePoolName($poolName);
        $connection = ConnectionCenter::getRequestContextConnection($poolName);

        return $connection->getInstance();
    }

    /**
     * 使用回调来使用池子中的资源，无需手动释放
     * 回调有两个参数：$connection(连接对象), $instance(操作实例对象，Redis实例)
     * 本方法返回值为回调的返回值
     */
    public static function use(?string $poolName, callable $callable): mixed
    {
        $poolName = self::parsePoolName($poolName);

        if (ConnectionCenter::hasConnectionManager($poolName))
        {
            $connection = ConnectionCenter::getConnection($poolName);

            return $callable($connection, $connection->getInstance());
        }
        else
        {
            $connection = ConnectionCenter::getRequestContextConnection($poolName);

            return $callable($connection, $connection->getInstance());
        }
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
}
