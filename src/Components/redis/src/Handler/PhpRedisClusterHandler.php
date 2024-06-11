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

    public function getClientVersion(): string
    {
        return phpversion('redis');
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
            // @phpstan-ignore-next-line
            $this->client->ping($node);
        }

        return true;
    }

    public function flushdbEx(): bool
    {
        foreach ($this->getNodes() as $node)
        {
            // @phpstan-ignore-next-line
            $this->client->flushDB($node);
        }

        return true;
    }

    public function flushallEx(): bool
    {
        foreach ($this->getNodes() as $node)
        {
            // @phpstan-ignore-next-line
            $this->client->flushAll($node);
        }

        return true;
    }

    public function scan(?int &$iterator, array|string $node, ?string $pattern = null, int $count = 0): array|false
    {
        // @phpstan-ignore-next-line
        return $this->client->scan($iterator, $node, $pattern, $count);
    }

    public function isSupportSerialize(): bool
    {
        return true;
    }
}
