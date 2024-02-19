<?php

declare(strict_types=1);

namespace Imi\Swoole\Server\ConnectionContext\StoreHandler;

use Imi\Bean\Annotation\Bean;
use Imi\Lock\Lock;
use Imi\Redis\Redis;
use Imi\Redis\RedisHandler;
use Imi\Server\ConnectionContext\StoreHandler\IHandler;
use Imi\Swoole\Util\MemoryTableManager;
use Imi\Timer\Timer;
use Imi\Worker;

/**
 * 连接上下文存储处理器-MemoryTable.
 */
#[Bean(name: 'ConnectionContextMemoryTable', env: 'swoole')]
class MemoryTable implements IHandler
{
    /**
     * 数据写入前编码回调.
     *
     * @var callable|null
     */
    protected $dataEncode = 'serialize';

    /**
     * 数据读出后处理回调.
     *
     * @var callable|null
     */
    protected $dataDecode = 'unserialize';

    /**
     * 表名.
     */
    protected string $tableName = '';

    /**
     * Redis 连接池名称.
     */
    protected ?string $redisPool = null;

    /**
     * redis中第几个库.
     */
    protected ?int $redisDb = null;

    /**
     * 键.
     */
    protected string $key = 'imi:connectionBinder:map';

    /**
     * 锁 ID.
     */
    protected ?string $lockId = null;

    public function __init(): void
    {
        if (0 === Worker::getWorkerId())
        {
            $this->useRedis(function (RedisHandler $redis): void {
                $key = $this->key;
                $keys = (array) $key;
                $count = 0;
                foreach ($redis->scanEach($key . ':*') as $key)
                {
                    $keys[] = $key;
                    if (++$count >= 1000)
                    {
                        $redis->del($keys);
                        $keys = [];
                        $count = 0;
                    }
                }
                if ($keys)
                {
                    $redis->del($keys);
                }
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $key): array
    {
        $result = MemoryTableManager::get($this->tableName, $key, 'data');
        if ($result)
        {
            if ($this->dataDecode)
            {
                return ($this->dataDecode)($result);
            }
            else
            {
                return $result;
            }
        }
        else
        {
            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function save(string $key, array $data): void
    {
        if ($this->dataEncode)
        {
            $data = ($this->dataEncode)($data);
        }
        MemoryTableManager::set($this->tableName, $key, ['data' => $data]);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $key): void
    {
        MemoryTableManager::del($this->tableName, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function delayDestroy(string $key, int $ttl): void
    {
        Timer::after($ttl * 1000, function () use ($key): void {
            $this->destroy($key);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $key): bool
    {
        return MemoryTableManager::exist($this->tableName, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function lock(string $key, ?callable $callable = null): bool
    {
        if ($this->lockId)
        {
            return Lock::getInstance($this->lockId, $key)->lock($callable);
        }
        else
        {
            return MemoryTableManager::lock($this->tableName, $callable);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unlock(): bool
    {
        if ($this->lockId)
        {
            return Lock::unlock($this->lockId);
        }
        else
        {
            return MemoryTableManager::unlock($this->tableName);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function bind(string $flag, int|string $clientId): void
    {
        $this->lock((string) $clientId, function () use ($flag, $clientId): void {
            $data = $this->read((string) $clientId);
            $data['__flag'] = $flag;
            $this->save((string) $clientId, $data);
        });
        $this->useRedis(function (RedisHandler $redis) use ($flag, $clientId): void {
            $redis->hSet($this->key . ':binder', $flag, $clientId);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function bindNx(string $flag, int|string $clientId): bool
    {
        $result = $this->useRedis(fn (RedisHandler $redis) => $redis->hSetNx($this->key . ':binder', $flag, $clientId));
        if ($result)
        {
            $this->lock((string) $clientId, function () use ($flag, $clientId): void {
                $data = $this->read((string) $clientId);
                $data['__flag'] = $flag;
                $this->save((string) $clientId, $data);
            });
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function unbind(string $flag, int|string $clientId, ?int $keepTime = null): void
    {
        $this->useRedis(function (RedisHandler $redis) use ($flag, $clientId, $keepTime): void {
            $key = $this->key . ':binder';
            $this->lock((string) $clientId, function () use ($flag, $clientId): void {
                $data = $this->read((string) $clientId);
                $data['__flag'] = $flag;
                $this->save((string) $clientId, $data);
            });
            $redis->multi();
            $redis->hDel($key, $flag);
            if ($keepTime > 0)
            {
                $redis->set($key . ':old:' . $flag, $clientId, $keepTime);
            }
            $redis->exec();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getClientIdByFlag(string $flag): array
    {
        return (array) $this->useRedis(fn (RedisHandler $redis) => $redis->hGet($this->key . ':binder', $flag) ?: null);
    }

    /**
     * {@inheritDoc}
     */
    public function getClientIdsByFlags(array $flags): array
    {
        $result = $this->useRedis(fn (RedisHandler $redis) => $redis->hMget($this->key . ':binder', $flags));
        foreach ($result as $k => $v)
        {
            $result[$k] = (array) $v;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getFlagByClientId(int|string $clientId): ?string
    {
        return $this->read((string) $clientId)['__flag'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getFlagsByClientIds(array $clientIds): array
    {
        $flags = [];
        foreach ($clientIds as $clientId)
        {
            $flags[$clientId] = $this->read((string) $clientId)['__flag'] ?? null;
        }

        return $flags;
    }

    /**
     * {@inheritDoc}
     */
    public function getOldClientIdByFlag(string $flag): ?int
    {
        return $this->useRedis(fn (RedisHandler $redis) => $redis->get($this->key . ':binder:old:' . $flag) ?: null);
    }

    /**
     * 使用redis.
     */
    private function useRedis(callable $callback): mixed
    {
        return Redis::use(function (RedisHandler $redis) use ($callback) {
            if (null !== $this->redisDb)
            {
                $redis->select($this->redisDb);
            }

            return $callback($redis);
        }, $this->redisPool);
    }
}
