<?php

declare(strict_types=1);

namespace Imi\Redis\Traits;

use Imi\Redis\RedisManager;
use Imi\Redis\RedisResource;

/**
 * @deprecated
 */
trait TRedisPool
{
    /**
     * 创建资源.
     *
     * @return \Imi\Redis\RedisResource
     */
    public function createNewResource(): \Imi\Pool\Interfaces\IPoolResource
    {
        $config = $this->getNextResourceConfig();

        $redis = RedisManager::resolveRedisConnection($config);

        return new RedisResource($this, $redis, $config);
    }
}
