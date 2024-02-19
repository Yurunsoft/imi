<?php

declare(strict_types=1);

namespace Imi\Redis\Traits;

use Imi\Redis\Handler\IRedisClusterHandler;
use Predis\PredisException;

trait TPredisMethod
{
    public function _serialize(mixed $value): ?string
    {
        return $value;
    }

    public function _unserialize(?string $value): mixed
    {
        return $value;
    }

    public function getDBNum(): int
    {
        // 不建议使用，性能差
        if ($this instanceof IRedisClusterHandler)
        {
            return 0;
        }

        $clientId = $this->client->client('id');
        $info = $this->client->client('list');

        foreach ($info as $item)
        {
            if ($item['id'] === $clientId)
            {
                return $item['db'] ?? 0;
            }
        }

        return 0;
    }

    /**
     * eval扩展方法，结合了 eval、evalSha.
     *
     * 优先使用 evalSha 尝试，失败则使用 eval 方法
     */
    public function evalEx(string $script, ?array $args = null, ?int $numKeys = null): mixed
    {
        $sha1 = sha1($script);
        $client = $this->client;

        try
        {
            return $client->evalSha($sha1, $numKeys, ...$args);
        }
        catch (PredisException $exception)
        {
            if ('NOSCRIPT No matching script. Please use EVAL.' === $exception->getMessage())
            {
                return $client->eval($script, $numKeys, ...$args);
            }
            else
            {
                throw $exception;
            }
        }
    }

    public function scanEach(?string $pattern = null, int $count = 0): \Generator
    {
        if ($this instanceof IRedisClusterHandler)
        {
            foreach ($this->getNodes() as $node)
            {
                $cursor = null;
                do
                {
                    // @phpstan-ignore-next-line
                    $result = $this->scan("{$node[0]}:{$node[1]}", $cursor, ['match' => $pattern, 'count' => $count]);
                    [$cursor, $keys] = $result;
                    if ($keys)
                    {
                        yield from $keys;
                    }
                }
                while ($cursor > 0);
            }
        }
        else
        {
            $cursor = null;
            do
            {
                $result = $this->client->scan($cursor, ['match' => $pattern, 'count' => $count]);
                [$cursor, $keys] = $result;
                if ($keys)
                {
                    yield from $keys;
                }
            }
            while ($cursor > 0);
        }
    }

    public function hscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator
    {
        $cursor = null;
        do
        {
            $result = $this->client->hscan($key, $cursor, ['match' => $pattern, 'count' => $count]);
            [$cursor, $keys] = $result;
            if ($keys)
            {
                yield from $keys;
            }
        }
        while ($cursor > 0);
    }

    public function sscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator
    {
        $cursor = null;
        do
        {
            $result = $this->client->sscan($key, $cursor, ['match' => $pattern, 'count' => $count]);
            [$cursor, $keys] = $result;
            if ($keys)
            {
                yield from $keys;
            }
        }
        while ($cursor > 0);
    }

    public function zscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator
    {
        $cursor = null;
        do
        {
            $result = $this->client->zscan($key, $cursor, ['match' => $pattern, 'count' => $count]);
            [$cursor, $keys] = $result;
            if ($keys)
            {
                yield from $keys;
            }
        }
        while ($cursor > 0);
    }
}
