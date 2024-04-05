<?php

declare(strict_types=1);

namespace Imi\Cache\Handler;

use Imi\Bean\Annotation\Bean;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;
use Imi\Redis\RedisConnectionService;
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

    public function __construct(array $option = [], protected ?RedisConnectionService $redisManager = null)
    {
        parent::__construct($option);

        $this->redisManager ??= new RedisConnectionService();
        $this->redisManager->setPoolName($this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $result = $this
            ->redisManager
            ->getInstance()
            ->get($this->parseKey($key));
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

        $redis = $this->redisManager->getInstance();

        return (bool) match (true)
        {
            $redis instanceof PhpRedisHandler,
            $redis instanceof PhpRedisClusterHandler => $redis->set($this->parseKey($key), $this->encode($value), $ttl),
            $redis instanceof PredisHandler,
            $redis instanceof PredisClusterHandler => $ttl
                ? $redis->set($this->parseKey($key), $this->encode($value), 'ex', $ttl)
                : $redis->set($this->parseKey($key), $this->encode($value)),
            // @phpstan-ignore-next-line
            default => throw new \RuntimeException('Unsupported redis handler')
        };
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $redis = $this->redisManager->getInstance();

        return (int) $redis->del($this->parseKey($key)) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $redis = $this->redisManager->getInstance();

        return $redis->flushdbEx();
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
        unset($key);
        $mgetResult = $this->redisManager->getInstance()->mget($keys);
        $result = [];
        if ($mgetResult)
        {
            foreach ($mgetResult as $i => $v)
            {
                $key = $keys[$i];

                if ($this->prefix && str_starts_with((string) $key, $this->prefix))
                {
                    $key = substr((string) $key, \strlen($this->prefix));
                }

                if (false === $v || null === $v)
                {
                    $result[$key] = $default;
                }
                else
                {
                    $result[$key] = $this->decode($v);
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
        $values = [];
        foreach ($setValues as $k => $v)
        {
            $values[$this->parseKey((string) $k)] = $this->encode($v);
        }
        // ttl 支持 \DateInterval 格式
        if ($ttl instanceof \DateInterval)
        {
            $ttl = DateTime::getSecondsByInterval($ttl);
        }

        $redis = $this->redisManager->getInstance();
        if ($redis instanceof PredisClusterHandler)
        {
            throw new \RuntimeException('predis cluster not support setMultiple method');
        }
        $redis->multi();
        $redis->mset($values);
        if (null !== $ttl)
        {
            foreach ($values as $k => $v)
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

        $redis = $this->redisManager->getInstance();

        return (bool) $redis->del($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $redis = $this->redisManager->getInstance();

        return (int) $redis->exists($this->parseKey($key)) > 0;
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
