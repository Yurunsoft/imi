<?php

declare(strict_types=1);

namespace Imi\Redis;

use Imi\Pool\BaseSyncPool;
use Imi\Pool\TUriResourceConfig;
use Imi\Redis\Traits\TRedisPool;

/**
 * @deprecated
 */
class SyncRedisPool extends BaseSyncPool
{
    use TRedisPool;
    use TUriResourceConfig;

    /**
     * 数据库操作类.
     */
    protected string $handlerClass = \Redis::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(string $name, \Imi\Pool\Interfaces\IPoolConfig $config = null, mixed $resourceConfig = null)
    {
        parent::__construct($name, $config, $resourceConfig);
        $this->initUriResourceConfig();
    }
}
