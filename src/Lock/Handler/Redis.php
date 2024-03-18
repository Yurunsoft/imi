<?php

declare(strict_types=1);

namespace Imi\Lock\Handler;

use Imi\Bean\Annotation\Bean;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Redis as ImiRedis;
use Imi\Redis\RedisManager;

/**
 * Redis + Lua 实现的分布式锁，需要 Redis >= 2.6.0.
 * Lua脚本来自：https://blog.csdn.net/hry2015/article/details/74937375.
 */
#[Bean(name: 'RedisLock')]
class Redis extends BaseLock
{
    /**
     * Redis 连接池名称.
     */
    public ?string $poolName = null;

    /**
     * Redis 几号库.
     */
    public ?int $db = null;

    /**
     * 获得锁每次尝试间隔，单位：毫秒.
     */
    public int $waitSleepTime = 20;

    /**
     * Redis key.
     */
    public string $key = '';

    /**
     * Redis key 前置.
     */
    public string $keyPrefix = 'imi:lock:';

    /**
     * 当前锁对象GUID.
     */
    private string $guid = '';

    public function __construct(string $id, array $options = [])
    {
        parent::__construct($id, $options);
        if (null === $this->poolName)
        {
            $this->poolName = RedisManager::getDefaultPoolName();
        }
        $this->key = $this->keyPrefix . $id;
        $this->guid = bin2hex(random_bytes(8));
    }

    /**
     * {@inheritDoc}
     */
    protected function __lock(): bool
    {
        $beginTime = microtime(true);
        $waitTimeout = $this->waitTimeout;
        $waitSleepTime = $this->waitSleepTime;
        while (true)
        {
            if ($this->__tryLock())
            {
                return true;
            }
            if (0 === $waitTimeout || microtime(true) - $beginTime < $waitTimeout / 1000)
            {
                usleep($waitSleepTime * 1000);
            }
            else
            {
                break;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function __tryLock(): bool
    {
        return ImiRedis::use(
            fn ($redis): bool =>
                /** @var PhpRedisHandler $redis */
                (bool) $redis->evalEx(
                    <<<'SCRIPT'
                    local key     = KEYS[1]
                    local content = ARGV[1]
                    local ttl     = ARGV[3]
                    local db      = tonumber(ARGV[2])
                    if db then
                        redis.call('select', db)
                    end
                    local lockSet = redis.call('set', key, content, 'NX', 'PX', ttl)
                    if lockSet == 0 then
                        local value = redis.call('get', key)
                        if(value == content) then
                            lockSet = 1;
                            redis.call('pexpire', key, ttl)
                        end
                    end
                    return lockSet
                    SCRIPT,
                    [
                        $this->key,
                        $this->guid,
                        $this->db,
                        $this->lockExpire,
                    ],
                    1
                ),
            $this->poolName,
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function __unlock(): bool
    {
        return ImiRedis::use(
            function ($redis): bool {
                /** @var PhpRedisHandler $redis */
                $result = $redis->evalEx(
                    <<<'SCRIPT'
                    local key     = KEYS[1]
                    local content = ARGV[1]
                    local db      = tonumber(ARGV[2])
                    if db then
                        redis.call('select', db)
                    end
                    local value = redis.call('get', key)
                    if value == content then
                        return redis.call('del', key);
                    end
                    return 0
                    SCRIPT,
                    [
                        $this->key,
                        $this->guid,
                        $this->db,
                    ],
                    1
                );

                return $result > 0;
            },
            $this->poolName,
        );
    }
}
