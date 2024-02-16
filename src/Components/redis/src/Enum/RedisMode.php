<?php

declare(strict_types=1);

namespace Imi\Redis\Enum;

enum RedisMode
{
    /**
     * 单机.
     */
    case Standalone;

    /**
     * 集群模式.
     */
    case Cluster;

    /**
     * 哨兵模式.
     */
    case Sentinel;
}
