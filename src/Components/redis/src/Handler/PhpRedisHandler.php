<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

use Imi\Redis\Traits\TPhpRedisMethod;
use Redis;

/**
 * @mixin Redis
 */
class PhpRedisHandler extends AbstractRedisHandler implements IRedisHandler
{
    use TPhpRedisMethod;

    public function __construct(
        protected \Redis $client,
    ) {
    }

    public function getInstance(): \Redis
    {
        return $this->client;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->client->{$name}(...$arguments);
    }

    public function isConnected(): bool
    {
        $result = $this->client->ping();

        // PHPRedis 扩展，5.0.0 版本开始，ping() 返回为 true，旧版本为 +PONG
        return true === $result || '+PONG' === $result;
    }

    public function scan(?int &$iterator, ?string $pattern = null, int $count = 0, ?string $type = null)
    {
        return $this->client->scan($iterator, $pattern, $count, $type);
    }

    public function isSupportSerialize(): bool
    {
        return true;
    }
}
