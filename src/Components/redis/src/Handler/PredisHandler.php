<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

use Imi\Redis\Connector\RedisDriverConfig;
use Imi\Redis\Traits\TPredisMethod;
use Predis\Client;

/**
 * @mixin Client
 */
class PredisHandler extends AbstractRedisHandler implements IRedisHandler
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

    public function isConnected(): bool
    {
        return 'PONG' === (string) $this->client->ping();
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

    public function close(): void
    {
        $this->client->disconnect();
    }

    public function isSupportSerialize(): bool
    {
        return false;
    }
}
