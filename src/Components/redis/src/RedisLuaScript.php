<?php

declare(strict_types=1);

namespace Imi\Redis;

use Imi\Redis\Exception\RedisLuaException;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;

abstract class RedisLuaScript implements IRedisLuaScript
{
    protected ?string $name = null;

    protected ?string $luaSha1 = null;

    protected bool $readOnly = false;

    public function withReadOnly(): static
    {
        // phpredis 6.0 and redis >= 7.0 support readonly
        // todo 实施版本检查
        // @link https://redis.io/docs/interact/programmability/

        $script = clone $this;
        $script->reset();
        $script->readOnly = true;

        return $script;
    }

    public static function isSupportReadOnly(IRedisHandler $redis): bool
    {
        return version_compare($redis->getServerVersion(), '7.0', '>=');
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function reset(): void
    {
        $this->luaSha1 = null;
    }

    public function getSha1(): string
    {
        return $this->luaSha1 ??= sha1($this->script());
    }

    public function isLoaded(IRedisHandler $redis): bool
    {
        /** @var PhpRedisHandler|PredisHandler $redis */
        return $redis->script('exists', $this->getSha1())[0] > 0;
    }

    public function loadScript(IRedisHandler $redis): void
    {
        /** @var PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler $redis */
        if (!$this->isLoaded($redis))
        {
            if ($redis instanceof PhpRedisHandler || $redis instanceof PhpRedisClusterHandler)
            {
                $redis->clearLastError();
            }
            $result = $redis->script('load', $this->script());
            if (false === $result)
            {
                throw new RedisLuaException(static::class, $redis->getLastError());
            }
            if ($this->getSha1() !== $result)
            {
                throw new RedisLuaException(static::class, 'load lua script fail');
            }
        }
    }

    public function invoke(IRedisHandler $redis, array $keys, array $argv = []): mixed
    {
        $keysNum = $this->keysNum();

        if (\count($keys) !== $keysNum)
        {
            throw new \InvalidArgumentException('Script call keys count error.');
        }

        if ($this->readOnly && !self::isSupportReadOnly($redis))
        {
            throw new \RuntimeException('The redis server does not support readonly mode');
        }

        $retry = false;
        RETRY_EVAL:
        if ($redis instanceof PhpRedisHandler || $redis instanceof PhpRedisClusterHandler)
        {
            $redis->clearLastError();
        }
        try
        {
            if ($retry)
            {
                if ($redis instanceof PhpRedisHandler || $redis instanceof PhpRedisClusterHandler)
                {
                    if ($this->readOnly)
                    {
                        $result = $redis->eval_ro($this->script(), array_merge($keys, $argv), $keysNum);
                    }
                    else
                    {
                        $result = $redis->eval($this->script(), array_merge($keys, $argv), $keysNum);
                    }
                }
                elseif ($redis instanceof PredisHandler || $redis instanceof PredisClusterHandler)
                {
                    if ($this->readOnly)
                    {
                        $result = $redis->eval_ro($this->script(), $keys, ...$argv);
                    }
                    else
                    {
                        $result = $redis->eval($this->script(), $keysNum, ...$keys, ...$argv);
                    }
                }
                else
                {
                    throw new \RuntimeException('Unknown redis handler, ' . $redis::class);
                }
            }
            else
            {
                if ($redis instanceof PhpRedisHandler || $redis instanceof PhpRedisClusterHandler)
                {
                    if ($this->readOnly)
                    {
                        $result = $redis->evalsha_ro($this->getSha1(), array_merge($keys, $argv), $keysNum);
                    }
                    else
                    {
                        $result = $redis->evalSha($this->getSha1(), array_merge($keys, $argv), $keysNum);
                    }
                }
                elseif ($redis instanceof PredisHandler || $redis instanceof PredisClusterHandler)
                {
                    if ($this->readOnly)
                    {
                        $result = $redis->evalsha_ro($this->getSha1(), $keys, ...$argv);
                    }
                    else
                    {
                        $result = $redis->evalsha($this->getSha1(), $keysNum, ...$keys, ...$argv);
                    }
                }
                else
                {
                    throw new \RuntimeException('Unknown redis handler, ' . $redis::class);
                }
            }
            if ($redis instanceof PhpRedisHandler || $redis instanceof PhpRedisClusterHandler)
            {
                if (false === $result && null !== ($error = $redis->getLastError()))
                {
                    throw new \RuntimeException($error);
                }
            }
        }
        catch (\Throwable $e)
        {
            if (false === $retry && str_starts_with($e->getMessage(), 'NOSCRIPT'))
            {
                $retry = true;
                goto RETRY_EVAL;
            }
            throw new RedisLuaException(static::class, $e->getMessage(), previous: $e);
        }

        return $result;
    }

    public function __invoke(IRedisHandler $redis, array $keys, array $argv = []): mixed
    {
        return $this->invoke($redis, $keys, $argv);
    }
}
