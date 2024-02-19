<?php

declare(strict_types=1);

namespace Imi\Server\Group\Handler;

use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\Event\Event;
use Imi\Redis\Redis as ImiRedis;
use Imi\Redis\RedisHandler;
use Imi\Swoole\Event\SwooleEvents;
use Imi\Timer\Timer;
use Imi\Worker;

#[Bean(name: 'GroupRedis')]
class Redis implements IGroupHandler
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
     * 心跳时间，单位：秒.
     */
    protected int $heartbeatTimespan = 5;

    /**
     * 心跳数据过期时间，单位：秒.
     */
    protected int $heartbeatTtl = 8;

    /**
     * 该服务的分组键.
     */
    protected string $key = '';

    /**
     * 心跳Timer的ID.
     */
    private ?int $timerId = null;

    /**
     * 组配置.
     */
    private array $groups = [];

    /**
     * 主进程 ID.
     */
    private int $masterPID = 0;

    /**
     * 启动时执行.
     */
    public function startup(): void
    {
        $this->clear();
    }

    public function __init(): void
    {
        if ('' === $this->key)
        {
            $this->key = 'imi:' . App::getNamespace() . ':connect_group';
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
            // 清空分组列表
            $groupsKey = $this->getGroupsKey();

            $it = null;
            while (false !== ($list = $redis->sScan($groupsKey, $it, '*', 1000)))
            {
                foreach ($list as $groupName)
                {
                    $redis->del($this->getGroupNameKey($groupName));
                }
                if (0 == $it)
                {
                    break;
                }
            }

            $redis->del($groupsKey);
        }
    }

    /**
     * 获取存放组名的set键.
     */
    private function getGroupsKey(): string
    {
        return $this->key . ':groups';
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
    }

    /**
     * {@inheritDoc}
     */
    public function hasGroup(string $groupName): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function createGroup(string $groupName, int $maxClients = -1): void
    {
        $groups = &$this->groups;
        if (!isset($groups[$groupName]))
        {
            $this->useRedis(function (RedisHandler $redis) use ($groupName): void {
                $redis->sAdd($this->getGroupsKey(), $groupName);
            });
            $groups[$groupName] = [
                'maxClient' => $maxClients,
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function closeGroup(string $groupName): void
    {
        $key = $this->getGroupNameKey($groupName);
        $this->useRedis(function (RedisHandler $redis) use ($key, $groupName): void {
            $redis->multi();
            $redis->del($key);
            $redis->srem($this->getGroupsKey(), $groupName);
            $redis->exec();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function joinGroup(string $groupName, int|string $clientId): bool
    {
        $key = $this->getGroupNameKey($groupName);

        return $this->useRedis(static fn (RedisHandler $redis): bool => $redis->sAdd($key, $clientId) > 0);
    }

    /**
     * {@inheritDoc}
     */
    public function leaveGroup(string $groupName, int|string $clientId): bool
    {
        $key = $this->getGroupNameKey($groupName);

        return $this->useRedis(static fn (RedisHandler $redis): bool => $redis->srem($key, $clientId) > 0);
    }

    /**
     * {@inheritDoc}
     */
    public function isInGroup(string $groupName, int|string $clientId): bool
    {
        $key = $this->getGroupNameKey($groupName);

        return $this->useRedis(static fn (RedisHandler $redis): bool => $redis->sismember($key, $clientId));
    }

    /**
     * {@inheritDoc}
     */
    public function getClientIds(string $groupName): array
    {
        $groups = $this->groups;

        $key = $this->getGroupNameKey($groupName);
        $result = $this->useRedis(static function (RedisHandler $redis) use ($key, $groupName, $groups): array {
            if ($groups[$groupName]['maxClient'] > 0)
            {
                return $redis->sRandMember($key, $groups[$groupName]['maxClient']);
            }
            else
            {
                return $redis->sMembers($key);
            }
        });

        foreach ($result as &$value)
        {
            $value = (int) $value;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupNameKey(string $groupName): string
    {
        return $this->key . ':groups:' . $groupName;
    }

    /**
     * {@inheritDoc}
     */
    public function count(string $groupName): int
    {
        $key = $this->getGroupNameKey($groupName);

        return $this->useRedis(static fn (RedisHandler $redis): int => $redis->scard($key));
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->useRedis(function (RedisHandler $redis): void {
            $keys = [];
            $count = 0;
            foreach ($redis->scanEach($this->getGroupNameKey('*')) as $key)
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

    /**
     * 使用redis.
     */
    private function useRedis(callable $callback): mixed
    {
        return ImiRedis::use(function (RedisHandler $redis) use ($callback) {
            if (null !== $this->redisDb && !$redis->isCluster())
            {
                $redis->select($this->redisDb);
            }

            return $callback($redis);
        }, $this->redisPool);
    }
}
