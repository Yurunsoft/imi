<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

use Imi\Redis\Connector\RedisDriverConfig;
use Imi\Redis\Traits\TPhpRedisMethod;
use RedisCluster;

/**
 * @mixin RedisCluster
 */
class PhpRedisClusterHandler extends AbstractRedisHandler implements IRedisClusterHandler
{
    use TPhpRedisMethod;

    public function __construct(
        protected \RedisCluster $client,
        protected RedisDriverConfig $config,
    ) {
    }

    public function getInstance(): \RedisCluster
    {
        return $this->client;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->client->{$name}(...$arguments);
    }

    public function getNodes(): array
    {
        return $this->client->_masters();
    }

    public function isConnected(): bool
    {
        foreach ($this->getNodes() as $node)
        {
            $this->client->ping($node);
        }

        return true;
    }

    /**
     * scan.
     */
    public function scan(?int &$iterator, array|string $node, ?string $pattern = null, int $count = 0): array
    {
        return $this->client->scan($iterator, $node, $pattern, $count);
    }

    public function isSupportSerialize(): bool
    {
        return true;
    }
}
