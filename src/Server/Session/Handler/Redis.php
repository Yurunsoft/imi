<?php

declare(strict_types=1);

namespace Imi\Server\Session\Handler;

use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\Redis\Redis as ImiRedis;

#[Bean(name: 'SessionRedis')]
class Redis extends Base
{
    /**
     * {@inheritDoc}
     */
    protected string $poolName = '';

    /**
     * Redis中存储的key前缀，可以用于多系统session的分离.
     */
    protected string $keyPrefix = '';

    public function __init(): void
    {
        parent::__init();
        if ('' === $this->keyPrefix)
        {
            $this->keyPrefix = 'imi:' . App::getNamespace() . ':';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $sessionId): void
    {
        ImiRedis::use(function (\Imi\Redis\RedisHandler $redis) use ($sessionId): void {
            $redis->del($this->getKey($sessionId));
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function gc(int $maxLifeTime): void
    {
        // 用redis数据自动过期，这里什么都不需要做
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $sessionId): string
    {
        return ImiRedis::use(fn (\Imi\Redis\RedisHandler $redis) => $redis->get($this->getKey($sessionId)), $this->poolName) ?: '';
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $sessionId, string $sessionData, int $maxLifeTime): void
    {
        ImiRedis::use(function (\Imi\Redis\RedisHandler $redis) use ($sessionId, $sessionData, $maxLifeTime): void {
            $redis->set($this->getKey($sessionId), $sessionData, $maxLifeTime);
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function getKey(string $sessionId): string
    {
        return $this->keyPrefix . $sessionId;
    }
}
