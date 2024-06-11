<?php

declare(strict_types=1);

namespace Imi\Snowflake;

use Godruoyi\Snowflake\SequenceResolver;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\RedisManager;

class ImiRedisResolver implements SequenceResolver
{
    /**
     * The cache prefix.
     */
    protected string $prefix = '';

    public const SEQUENCE_LUA = <<<'LUA'
    if(redis.call('exists',KEYS[1])<1 and redis.call('psetex',KEYS[1],ARGV[2],ARGV[1]))
    then
        return 0
    else
        return redis.call('incrby', KEYS[1], 1)
    end
    LUA;

    public function __construct(
        /**
         * Redis 连接池名称.
         *
         * 为 NULL 则使用默认连接池
         */
        protected ?string $redisPool = null
    ) {
    }

    /**
     *  {@inheritdoc}
     */
    public function sequence(int $currentTime)
    {
        $redis = RedisManager::getInstance($this->redisPool);
        /** @var $redis IRedisHandler */

        return $redis->evalEx(static::SEQUENCE_LUA, [$this->prefix . $currentTime, 1, 1000], 1);
    }

    /**
     * Set cacge prefix.
     */
    public function setCachePrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }
}
