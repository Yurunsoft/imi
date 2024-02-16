<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

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
        private Client $client,
    ) {
    }

    public function getInstance(): Client
    {
        return $this->client;
    }

    public function getNodes(): array
    {
        // 详细说明: https://github.com/predis/predis/issues/571#issuecomment-678300308
        /** @var RedisCluster $connection */
        $connection = $this->client->getConnection();
        $nodes = $connection->getSlotMap()->getNodes();

        return \array_map(function ($node) {
            $arr = \explode(':', $node);
            $arr[1] = (int) $arr[1];
            return $arr;
        }, $nodes);
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

    public function __call(string $name, array $arguments): mixed
    {
        $result = $this->client->{$name}(...$arguments);

        if ($result instanceof \Predis\Response\Status && 'OK' === (string) $result)
        {
            return true;
        }

        return $result;
    }

    /**
     * scan.
     */
    public function scan(string $node, $cursor, $options): array
    {
        return $this->client->getClientBy('id', $node)->scan($cursor, $options);
    }

    public function close(): void
    {
        $this->client->disconnect();
    }

    public function isSupportSerialize(): bool
    {
        return false;
    }
}
