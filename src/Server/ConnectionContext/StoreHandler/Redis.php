<?php

declare(strict_types=1);

namespace Imi\Server\ConnectionContext\StoreHandler;

use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\Event\Event;
use Imi\Lock\Lock;
use Imi\Redis\Redis as ImiRedis;
use Imi\Redis\RedisHandler;
use Imi\Swoole\Event\SwooleEvents;
use Imi\Timer\Timer;
use Imi\Worker;

/**
 * 连接上下文存储处理器-Redis.
 */
#[Bean(name: 'ConnectionContextRedis')]
class Redis implements IHandler
{
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
    protected string $key = '';

    /**
     * 心跳时间，单位：秒.
     */
    protected int $heartbeatTimespan = 5;

    /**
     * 心跳数据过期时间，单位：秒.
     */
    protected int $heartbeatTtl = 8;

    /**
     * 数据写入前编码回调.
     *
     * @var callable|null
     */
    protected $dataEncode = null;

    /**
     * 数据读出后处理回调.
     *
     * @var callable|null
     */
    protected $dataDecode = null;

    /**
     * 锁 ID.
     */
    protected ?string $lockId = null;

    /**
     * 心跳Timer的ID.
     */
    private ?int $timerId = null;

    /**
     * 主进程 ID.
     */
    private int $masterPID = 0;

    /**
     * 销毁数据的定时器ID.
     */
    private ?int $destroyTimerId = null;

    /**
     * 要销毁的键名数组.
     */
    private array $destroyKeys = [];

    public function __init(): void
    {
        if ('' === $this->key)
        {
            $this->key = 'imi:' . App::getNamespace() . ':connect_context';
        }
        $workerId = Worker::getWorkerId();
        $this->masterPID = $masterPID = Worker::getMasterPid();

        if (0 === $workerId)
        {
            $this->useRedis(function (RedisHandler $redis) use ($masterPID): void {
                $this->initRedis($redis, $masterPID);
                $this->startPing($redis);
            });
        }

        $this->startDestroyTimer();
    }

    /**
     * 初始化redis数据.
     */
    private function initRedis(RedisHandler $redis, ?int $storeMasterPID = null): void
    {
        if (null !== $storeMasterPID)
        {
            $redis->del($this->key . ':master_pid');
        }
        if ($redis->setnx($this->key . ':master_pid', $this->masterPID))
        {
            $key = $this->key . ':binder';
            $keys = [
                // 清空存储列表
                $this->getStoreKey(),
                $key,
            ];
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
        }
    }

    /**
     * 开始ping.
     */
    private function startPing(RedisHandler $redis): void
    {
        if ($this->ping($redis))
        {
            // 心跳定时器
            $this->timerId = Timer::tick($this->heartbeatTimespan * 1000, $this->pingTimer(...));
            // Swoole 兼容
            if (class_exists(SwooleEvents::class))
            {
                Event::on(SwooleEvents::SERVER_WORKER_EXIT, function (): void {
                    if ($this->timerId)
                    {
                        Timer::del($this->timerId);
                        $this->timerId = null;
                    }
                }, \Imi\Util\ImiPriority::IMI_MIN);
            }
        }
    }

    /**
     * ping定时器执行操作.
     */
    public function pingTimer(): void
    {
        $this->useRedis(function (RedisHandler $redis): void {
            $this->ping($redis);
        });
    }

    /**
     * 获取redis中存储ping的key.
     */
    private function getPingKey(): string
    {
        return $this->key . ':ping';
    }

    /**
     * ping操作.
     */
    private function ping(RedisHandler $redis): bool
    {
        return (bool) $redis->set($this->getPingKey(), '', $this->heartbeatTtl);
    }

    public function __destruct()
    {
        if (null !== $this->timerId)
        {
            Timer::del($this->timerId);
        }
        if (null !== $this->destroyTimerId)
        {
            Timer::del($this->destroyTimerId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $key): array
    {
        return $this->useRedis(function (RedisHandler $redis) use ($key) {
            $result = $redis->hGet($this->getStoreKey(), $key);
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
        });
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
        $this->useRedis(function (RedisHandler $redis) use ($key, $data): void {
            $redis->hSet($this->getStoreKey(), $key, $data);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $key): void
    {
        $this->useRedis(function (RedisHandler $redis) use ($key): void {
            $redis->hDel($this->getStoreKey(), $key);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delayDestroy(string $key, int $ttl): void
    {
        Timer::after($ttl * 1000, fn () => $this->destroyKeys[] = $key);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $key): bool
    {
        return $this->useRedis(fn (RedisHandler $redis) => $redis->hExists($this->getStoreKey(), $key));
    }

    /**
     * 获取存储hash键名.
     */
    private function getStoreKey(): string
    {
        return $this->key . ':store';
    }

    /**
     * 使用redis.
     */
    private function useRedis(callable $callback): mixed
    {
        return ImiRedis::use(function (RedisHandler $redis) use ($callback) {
            if (null !== $this->redisDb)
            {
                $redis->select($this->redisDb);
            }

            return $callback($redis);
        }, $this->redisPool);
    }

    /**
     * {@inheritDoc}
     */
    public function lock(string $key, ?callable $callable = null): bool
    {
        if (null === $this->lockId)
        {
            $callable();

            return true;
        }
        else
        {
            return Lock::getInstance($this->lockId, $key)->lock($callable);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function unlock(): bool
    {
        if (null === $this->lockId)
        {
            return true;
        }
        else
        {
            return Lock::unlock($this->lockId);
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

    private function startDestroyTimer(): void
    {
        Timer::tick(1000, function (): void {
            if ($keys = $this->destroyKeys)
            {
                $this->destroyKeys = [];
                $storeKey = $this->getStoreKey();
                $this->useRedis(static function (RedisHandler $redis) use ($keys, $storeKey): void {
                    foreach (array_chunk($keys, 1000) as $keysChunk)
                    {
                        $redis->hDel($storeKey, ...$keysChunk);
                    }
                });
            }
        });
    }
}
