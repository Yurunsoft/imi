<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

use Imi\Redis\Connector\RedisDriverConfig;
use Imi\Redis\Traits\TPredisMethod;
use Predis\Client;
use Predis\Connection\Cluster\RedisCluster;

/**
 * @mixin Client
 */
class PredisClusterHandler extends AbstractRedisHandler implements IRedisClusterHandler
{
    use TPredisMethod;

    public function __construct(
        protected Client $client,
        protected RedisDriverConfig $config,
    ) {
    }

    public function getInstance(): Client
    {
        return $this->client;
    }

    public function getClientVersion(): string
    {
        return Client::VERSION;
    }

    public function getNodes(): array
    {
        // 详细说明: https://github.com/predis/predis/issues/571#issuecomment-678300308
        /** @var RedisCluster $cluster */
        $cluster = $this->client->getConnection();

        $nodes = [];
        foreach ($cluster as $node)
        {
            /** @var \Predis\Connection\StreamConnection $node */
            $arr = explode(':', (string) $node);
            $arr[1] = (int) $arr[1];

            $nodes[] = $arr;
        }

        return $nodes;
    }

    public function isConnected(): bool
    {
        foreach ($this->getNodes() as $node)
        {
            $client = $this->client->getClientBy('id', "{$node[0]}:{$node[1]}");
            if ('PONG' !== (string) $client->ping())
            {
                return false;
            }
        }

        return true;
    }

    public function flushdbEx(): bool
    {
        foreach ($this->getNodes() as $node)
        {
            $client = $this->client->getClientBy('id', "{$node[0]}:{$node[1]}");
            if ('OK' !== (string) $client->flushdb())
            {
                return false;
            }
        }

        return true;
    }

    public function flushallEx(): bool
    {
        foreach ($this->getNodes() as $node)
        {
            $client = $this->client->getClientBy('id', "{$node[0]}:{$node[1]}");
            if ('OK' !== (string) $client->flushall())
            {
                return false;
            }
        }

        return true;
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = $this->client->{$name}(...$arguments);

        if ($result instanceof \Predis\Response\Status && 'OK' === (string) $result)
        {
            return true;
        }

        return $result;
    }

    public function scan(string $node, null|string|int $cursor, ?array $options): array
    {
        return $this->client->getClientBy('id', $node)->scan($cursor, $options);
    }

    public function close(): void
    {
        $this->client->disconnect();
    }

    public function getSlotGroupByKeys(array $keys): array
    {
        // https://gist.github.com/nrk/e07311af2316ea7e51719735274ded94

        /** @var \Predis\Connection\Cluster\RedisCluster $connection */
        $connection = $this->client->getConnection();
        $strategy = $connection->getClusterStrategy();
        $keysBySlot = [];
        foreach ($keys as $key)
        {
            $slot = $strategy->getSlotByKey($key);

            if (isset($keysBySlot[$slot]))
            {
                $keysBySlot[$slot][] = $key;
            }
            else
            {
                $keysBySlot[$slot] = [$key];
            }
        }

        return $keysBySlot;
    }

    public function isSupportSerialize(): bool
    {
        return false;
    }
}
