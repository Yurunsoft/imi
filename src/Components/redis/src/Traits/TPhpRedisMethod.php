<?php

declare(strict_types=1);

namespace Imi\Redis\Traits;

use Imi\Redis\Handler\IRedisClusterHandler;

trait TPhpRedisMethod
{
    private ?array $serverInfoCache = null;

    public function _serialize(mixed $value): ?string
    {
        return $this->client->_serialize($value);
    }

    public function _unserialize(?string $value): mixed
    {
        return $this->client->_unserialize($value);
    }

    public function getServerVersion(): string
    {
        if (null === $this->serverInfoCache)
        {
            $this->serverInfoCache = $this->client->info('server');
        }

        return $this->serverInfoCache['redis_version'];
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
        $client->clearLastError();

        $result = $client->evalSha($sha1, $args, $numKeys);
        $error = $client->getLastError();
        if ($error)
        {
            if ('NOSCRIPT No matching script. Please use EVAL.' === $error)
            {
                $client->clearLastError();
                $result = $client->eval($script, $args, $numKeys);
                $error = $client->getLastError();
                if ($error)
                {
                    throw new \RedisException($error);
                }
            }
            else
            {
                throw new \RedisException($error);
            }
        }

        return $result;
    }

    /**
     * scan 方法的扩展简易遍历方法.
     */
    public function scanEach(?string $pattern = null, int $count = 0): \Generator
    {
        if ($this instanceof IRedisClusterHandler)
        {
            foreach ($this->getNodes() as $node)
            {
                $it = null;
                do
                {
                    // @phpstan-ignore-next-line
                    $keys = $this->client->scan($it, $node, $pattern, $count);

                    if ($keys)
                    {
                        yield from $keys;
                    }
                }
                while ($it > 0);
            }
        }
        else
        {
            /** @var int|null $it */
            $it = null;
            do
            {
                $keys = $this->client->scan($it, $pattern, $count);

                if ($keys)
                {
                    yield from $keys;
                }
            }
            while ($it > 0);
        }
    }

    /**
     * hscan.
     *
     * @see \Redis::hscan()
     */
    public function hscan(string $key, ?int &$iterator, ?string $pattern = null, int $count = 0): mixed
    {
        return $this->client->hscan($key, $iterator, $pattern, $count);
    }

    /**
     * hscan 方法的扩展简易遍历方法.
     */
    public function hscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator
    {
        $it = null;
        do
        {
            $result = $this->client->hscan($key, $it, $pattern, $count);
            if ($result)
            {
                yield from $result;
            }
        }
        while ($it > 0);
    }

    /**
     * sscan.
     */
    public function sscan(string $key, ?int &$iterator, ?string $pattern = null, int $count = 0): mixed
    {
        return $this->client->sscan($key, $iterator, $pattern, $count);
    }

    /**
     * sscan 方法的扩展简易遍历方法.
     */
    public function sscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator
    {
        $it = null;
        do
        {
            $result = $this->client->sscan($key, $it, $pattern, $count);
            if ($result)
            {
                yield from $result;
            }
        }
        while ($it > 0);
    }

    /**
     * zscan.
     */
    public function zscan(string $key, ?int &$iterator, ?string $pattern = null, int $count = 0): mixed
    {
        return $this->client->zscan($key, $iterator, $pattern, $count);
    }

    /**
     * zscan 方法的扩展简易遍历方法.
     */
    public function zscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator
    {
        $it = null;
        do
        {
            $result = $this->client->zscan($key, $it, $pattern, $count);
            if ($result)
            {
                yield from $result;
            }
        }
        while ($it > 0);
    }

    /**
     * geoadd.
     *
     * 当开启序列化后，经纬度会被序列化，并返回错误：ERR value is not a valid float
     *
     * 如下链接，官方认为这不算 BUG，所以这里做了一个兼容处理
     *
     * @see https://github.com/phpredis/phpredis/issues/1549
     * @see \Redis::geoadd
     */
    public function geoadd(string $key, float|string $lng, float|string $lat, string $member, mixed ...$other_triples_and_options): mixed
    {
        $serializer = $this->client->getOption(\Redis::OPT_SERIALIZER);
        $this->client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        try
        {
            return $this->client->geoadd($key, $lng, $lat, $member, ...$other_triples_and_options);
        }
        finally
        {
            $this->client->setOption(\Redis::OPT_SERIALIZER, $serializer);
        }
    }
}
