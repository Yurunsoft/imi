<?php

declare(strict_types=1);

namespace Imi\Cache\Handler;

use Imi\Bean\Annotation\Bean;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;
use Imi\Redis\Redis as ImiRedis;
use Imi\Util\DateTime;

#[Bean(name: 'RedisCache')]
class Redis extends Base
{
    /**
     * Redis连接池名称.
     */
    protected ?string $poolName = null;

    /**
     * 缓存键前缀
     */
    protected string $prefix = '';

    /**
     * 将 key 中的 "." 替换为 ":".
     */
    protected bool $replaceDot = false;

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $result = ImiRedis::use(fn ($redis) =>
            /** @var PhpRedisHandler $redis */
            $redis->get($this->parseKey($key)), $this->poolName);
        if (false === $result || null === $result)
        {
            return $default;
        }
        else
        {
            return $this->decode($result);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        // ttl 支持 \DateInterval 格式
        if ($ttl instanceof \DateInterval)
        {
            $ttl = DateTime::getSecondsByInterval($ttl);
        }

        return (bool) ImiRedis::use(fn ($redis) => match (true)
        {
            $redis instanceof PhpRedisHandler,
            $redis instanceof PhpRedisClusterHandler => $redis->set($this->parseKey($key), $this->encode($value), $ttl),
            $redis instanceof PredisHandler,
            $redis instanceof PredisClusterHandler => $ttl
                    ? $redis->set($this->parseKey($key), $this->encode($value), 'ex', $ttl)
                    : $redis->set($this->parseKey($key), $this->encode($value)),
            default => throw new \RuntimeException('Unsupported redis handler')
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        return (bool) ImiRedis::use(fn ($redis) =>
            /** @var PhpRedisHandler $redis */
            (int) $redis->del($this->parseKey($key)) > 0, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return (bool) ImiRedis::use(static fn ($redis) =>
            /** @var PhpRedisHandler $redis */
            $redis->flushdbEx(), $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as &$key)
        {
            $key = $this->parseKey($key);
        }
        $mgetResult = ImiRedis::use(static fn ($redis) =>
            /** @var PhpRedisHandler $redis */
            $redis->mget($keys), $this->poolName);
        $result = [];
        if ($mgetResult)
        {
            foreach ($mgetResult as $i => $v)
            {
                if (false === $v || null === $v)
                {
                    $result[$keys[$i]] = $default;
                }
                else
                {
                    $result[$keys[$i]] = $this->decode($v);
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        if ($values instanceof \Traversable)
        {
            $setValues = clone $values;
        }
        else
        {
            $setValues = $values;
        }
        foreach ($setValues as $k => $v)
        {
            $setValues[$this->parseKey((string) $k)] = $this->encode($v);
        }
        // ttl 支持 \DateInterval 格式
        if ($ttl instanceof \DateInterval)
        {
            $ttl = DateTime::getSecondsByInterval($ttl);
        }
        $result = ImiRedis::use(static function ($redis) use ($setValues, $ttl) {
            /** @var PhpRedisHandler $redis */
            $redis->multi();
            $redis->mset($setValues);
            if (null !== $ttl)
            {
                foreach ($setValues as $k => $v)
                {
                    $redis->expire((string) $k, $ttl);
                }
            }
            foreach ($redis->exec() as $result)
            {
                if (!$result)
                {
                    return false;
                }
            }

            return true;
        }, $this->poolName);

        return (bool) $result;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as &$key)
        {
            $key = $this->parseKey($key);
        }

        return (bool) ImiRedis::use(static fn ($redis) =>
            /** @var PhpRedisHandler $redis */
            $redis->del($keys), $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return (bool) ImiRedis::use(fn ($redis) =>
            /** @var PhpRedisHandler $redis */
            $redis->exists($this->parseKey($key)), $this->poolName);
    }

    /**
     * 处理键.
     */
    public function parseKey(string $key): string
    {
        if ($this->replaceDot)
        {
            $key = str_replace('.', ':', $key);
        }
        if ($this->prefix)
        {
            $key = $this->prefix . $key;
        }

        return $key;
    }
}
