<?php

declare(strict_types=1);

namespace Imi\Cache\Handler;

use Imi\Bean\Annotation\Bean;
use Imi\Cache\InvalidArgumentException;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Redis;

#[Bean(name: 'RedisHashCache')]
class RedisHash extends Base
{
    public const GET_MULTIPLE_SCRIPT = <<<'SCRIPT'
    local key = KEYS[1]
    local result = {}
    for i = 1, #ARGV do
        table.insert(result, redis.call('hget', key, ARGV[i]))
    end
    return result
    SCRIPT;

    public const SET_MULTIPLE_SCRIPT = <<<'SCRIPT'
    local key = KEYS[1]
    local halfLen = #ARGV / 2
    for i = 1, halfLen do
        redis.call('hset', key, ARGV[i], ARGV[halfLen + i])
    end
    return true
    SCRIPT;

    public const DELETE_MULTIPLE_SCRIPT = <<<'SCRIPT'
    local key = KEYS[1]
    for i = 1, #ARGV do
        redis.call('hdel', key, ARGV[i])
    end
    return true
    SCRIPT;

    /**
     * Redis连接池名称.
     */
    protected ?string $poolName = null;

    /**
     * 默认缺省的 hash key.
     */
    protected string $defaultHashKey = 'imi:RedisHashCache';

    /**
     * 分隔符，分隔 hash key和 member.
     */
    protected string $separator = '->';

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->parseKey($key, $member);
        $result = Redis::use(static function ($redis) use ($key, $member) {
            /** @var PhpRedisHandler $redis */
            return $redis->hGet($key, $member);
        }, $this->poolName);
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
        $this->parseKey($key, $member);

        return false !== Redis::use(function ($redis) use ($key, $member, $value) {
            /** @var PhpRedisHandler $redis */
            return $redis->hSet($key, $member, $this->encode($value));
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $this->parseKey($key, $member);

        return Redis::use(static function ($redis) use ($key, $member) {
            /** @var PhpRedisHandler $redis */
            if (null === $member)
            {
                return (int) $redis->del($key) > 0;
            }
            else
            {
                return (int) $redis->hDel($key, $member) > 0;
            }
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return (bool) Redis::use(static function ($redis) {
            /** @var PhpRedisHandler $redis */
            return $redis->flushdbEx();
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keysMembers = [];
        foreach ($keys as $key)
        {
            $this->parseKey($key, $member);
            $keysMembers[$key][] = $member;
        }

        return Redis::use(function ($redis) use ($keysMembers, $default, $keys) {
            /** @var PhpRedisHandler $redis */
            $result = [];
            $i = 0;
            foreach ($keysMembers as $key => $members)
            {
                $evalResult = $redis->evalEx(self::GET_MULTIPLE_SCRIPT, array_merge(
                    [$key],
                    array_map($redis->_serialize(...), $members),
                ), 1);
                foreach ($evalResult as $v)
                {
                    if (false === $v || null === $v)
                    {
                        $result[$keys[$i]] = $default;
                    }
                    else
                    {
                        $result[$keys[$i]] = $this->decode($redis->_unserialize($v));
                    }
                    ++$i;
                }
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        if ($values instanceof \Traversable)
        {
            $_setValues = clone $values;
        }
        else
        {
            $_setValues = $values;
        }

        $setValues = [];
        foreach ($_setValues as $k => $v)
        {
            $k = (string) $k;
            $this->parseKey($k, $member);
            $setValues[$k]['member'][] = $member;
            $setValues[$k]['value'][] = $this->encode($v);
        }

        $result = Redis::use(static function ($redis) use ($setValues) {
            foreach ($setValues as $key => $item)
            {
                /** @var PhpRedisHandler $redis */
                $result = false !== $redis->evalEx(self::SET_MULTIPLE_SCRIPT, array_merge([$key], array_map($redis->_serialize(...), $item['member']), array_map($redis->_serialize(...), $item['value'])), 1);
                if (!$result)
                {
                    return $result;
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
        $keysMembers = [];
        foreach ($keys as $key)
        {
            $this->parseKey($key, $member);
            $keysMembers[$key][] = $member;
        }

        return (bool) Redis::use(static function ($redis) use ($keysMembers) {
            foreach ($keysMembers as $key => $members)
            {
                /** @var PhpRedisHandler $redis */
                $result = $redis->evalEx(self::DELETE_MULTIPLE_SCRIPT, array_merge(
                    [$key],
                    array_map($redis->_serialize(...), $members),
                ), 1);
                if (!$result)
                {
                    return $result;
                }
            }

            return true;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $this->parseKey($key, $member);

        return (bool) Redis::use(static function ($redis) use ($key, $member) {
            /** @var PhpRedisHandler $redis */
            return $redis->hExists($key, $member);
        }, $this->poolName);
    }

    /**
     * 处理key.
     */
    protected function parseKey(?string &$key, ?string &$member): void
    {
        if (!\is_string($key))
        {
            throw new InvalidArgumentException('Invalid key: ' . $key);
        }
        if ('' === $this->separator)
        {
            throw new InvalidArgumentException('Invalid separator, it must be not empty');
        }
        $list = explode($this->separator, $key);

        if (isset($list[1]))
        {
            $key = $list[0];
            $member = $list[1];
        }
        else
        {
            $key = $this->defaultHashKey;
            $member = $list[0];
        }
    }
}
