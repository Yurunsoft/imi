<?php

declare(strict_types=1);

namespace Imi\Queue\Driver;

use Imi\Bean\Annotation\Bean;
use Imi\Queue\Contract\IMessage;
use Imi\Queue\Enum\IQueueType;
use Imi\Queue\Enum\QueueType;
use Imi\Queue\Exception\QueueException;
use Imi\Queue\Model\Message;
use Imi\Queue\Model\QueueStatus;
use Imi\Redis\Redis;
use Imi\Util\Traits\TDataToProperty;

/**
 * Redis 队列驱动.
 */
#[Bean(name: 'RedisQueueDriver')]
class RedisQueueDriver implements IQueueDriver
{
    use TDataToProperty{
        __construct as private traitConstruct;
    }

    /**
     * Redis 连接池名称.
     */
    protected ?string $poolName = null;

    /**
     * 键前缀
     */
    protected string $prefix = 'imi:';

    /**
     * 循环尝试 pop 的时间间隔，单位：秒.
     */
    protected float $timespan = 0.03;

    private ?string $keyName = null;

    public function __construct(
        /**
         * 队列名称.
         */
        protected string $name, array $config = [])
    {
        $this->traitConstruct($config);
    }

    public function __init(): void
    {
        Redis::use(function (\Imi\Redis\RedisHandler $redis): void {
            if ($redis->isCluster())
            {
                $this->keyName = '{' . $this->name . '}';
            }
            else
            {
                $this->keyName = $this->name;
            }
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function push(IMessage $message, float $delay = 0, array $options = []): string
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($message, $delay) {
            if ($delay > 0)
            {
                $args = [
                    $this->getQueueKey(QueueType::Delay),
                    $this->getMessageKeyPrefix(),
                    $this->getMessageIdKey(),
                    microtime(true) + $delay,
                    date('Ymd'),
                ];
                foreach ($message->toArray() as $k => $v)
                {
                    $args[] = $k;
                    $args[] = $v;
                }
                $result = $redis->evalEx(<<<'LUA'
                local queueKey = KEYS[1]
                local messageKeyPrefix = KEYS[2]
                local messageIdKey = KEYS[3]
                local delayTo = ARGV[1]
                local date = ARGV[2]
                -- 创建消息id
                local messageId = redis.call('hIncrby', messageIdKey, date, 1);
                if messageId > 0 then
                    messageId = date .. messageId
                else
                    return false
                end
                -- 创建消息
                local messageKey = messageKeyPrefix .. messageId;
                local ARGVLength = table.getn(ARGV)
                for i=3,ARGVLength,2 do
                    redis.call('hset', messageKey, ARGV[i], ARGV[i + 1])
                end
                redis.call('hset', messageKey, 'messageId', messageId)
                -- 加入延时队列
                redis.call('zadd', queueKey, delayTo, messageId);
                return messageId
                LUA, $args, 3);
            }
            else
            {
                $args = [
                    $this->getQueueKey(QueueType::Ready),
                    $this->getMessageKeyPrefix(),
                    $this->getMessageIdKey(),
                    date('Ymd'),
                ];
                foreach ($message->toArray() as $k => $v)
                {
                    $args[] = $k;
                    $args[] = $v;
                }
                $result = $redis->evalEx(<<<'LUA'
                local queueKey = KEYS[1]
                local messageKeyPrefix = KEYS[2]
                local messageIdKey = KEYS[3]
                local date = ARGV[1]
                -- 创建消息id
                local messageId = redis.call('hIncrby', messageIdKey, date, 1);
                if messageId > 0 then
                    messageId = date .. messageId
                else
                    return false
                end
                -- 创建消息
                local messageKey = messageKeyPrefix .. messageId;
                local ARGVLength = table.getn(ARGV)
                for i=2,ARGVLength,2 do
                    redis.call('hset', messageKey, ARGV[i], ARGV[i + 1])
                end
                redis.call('hset', messageKey, 'messageId', messageId)
                -- 加入队列
                redis.call('rpush', queueKey, messageId);
                return messageId
                LUA, $args, 3);
            }
            if (false === $result)
            {
                if (null === ($error = $redis->getLastError()))
                {
                    throw new QueueException('Queue push failed');
                }
                else
                {
                    throw new QueueException('Queue push failed, ' . $error);
                }
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function pop(float $timeout = 0): ?IMessage
    {
        $time = $useTime = 0;
        do
        {
            if ($timeout > 0)
            {
                if ($time)
                {
                    $leftTime = $timeout - $useTime;
                    if ($leftTime > $this->timespan)
                    {
                        usleep((int) ($this->timespan * 1000000));
                    }
                }
                else
                {
                    $time = microtime(true);
                }
            }
            $result = Redis::use(function (\Imi\Redis\RedisHandler $redis) {
                $this->parseDelayMessages($redis);
                $this->parseTimeoutMessages($redis);
                $result = $redis->evalEx(<<<'LUA'
                -- 从列表弹出
                local messageId = redis.call('lpop', KEYS[1])
                if false == messageId then
                    return -1
                end
                -- 获取消息内容
                local hashResult = redis.call('hgetall', KEYS[3] .. messageId)
                local message = {}
                for i=1,#hashResult,2 do
                    message[hashResult[i]] = hashResult[i + 1]
                end
                -- 加入工作队列
                local score = tonumber(message.workingTimeout)
                if nil == score or score <= 0 then
                    score = -1
                end
                redis.call('zadd', KEYS[2], ARGV[1] + score, messageId)
                return hashResult
                LUA, [
                    $this->getQueueKey(QueueType::Ready),
                    $this->getQueueKey(QueueType::Working),
                    $this->getMessageKeyPrefix(),
                    microtime(true),
                ], 3);
                if (false === $result)
                {
                    if (null === ($error = $redis->getLastError()))
                    {
                        throw new QueueException('Queue pop failed');
                    }
                    else
                    {
                        throw new QueueException('Queue pop failed, ' . $error);
                    }
                }

                return $result;
            }, $this->poolName);
            if ($result && \is_array($result))
            {
                $data = [];
                $length = \count($result);
                for ($i = 0; $i < $length; $i += 2)
                {
                    $data[$result[$i]] = $result[$i + 1];
                }
                $message = new Message();
                $message->loadFromArray($data);

                return $message;
            }
            if ($timeout < 0)
            {
                return null;
            }
        }
        while (($useTime = (microtime(true) - $time)) < $timeout);

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(IMessage $message): bool
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($message) {
            $result = $redis->evalEx(<<<'LUA'
            local messageId = ARGV[1]
            -- 删除消息
            redis.call('del', KEYS[3] .. messageId)
            -- 从队列删除
            if redis.call('lrem', KEYS[1], 1, messageId) <= 0 then
                if redis.call('zrem', KEYS[2], messageId) <= 0 then
                    return false
                end
            end
            return true
            LUA, [
                $this->getQueueKey(QueueType::Ready),
                $this->getQueueKey(QueueType::Delay),
                $this->getMessageKeyPrefix(),
                $message->getMessageId(),
            ], 3);

            if (false === $result)
            {
                if (null === ($error = $redis->getLastError()))
                {
                    return false;
                }
                else
                {
                    throw new QueueException('Queue delete failed, ' . $error);
                }
            }

            return 1 == $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(?IQueueType $queueType = null): void
    {
        if (null === $queueType)
        {
            $queueType = QueueType::cases();
        }
        else
        {
            $queueType = (array) $queueType;
        }
        $keys = [];
        foreach ($queueType as $tmpQueueType)
        {
            $keys[] = $this->getQueueKey($tmpQueueType);
        }

        Redis::use(static function (\Imi\Redis\RedisHandler $redis) use ($keys): void {
            $redis->del(...$keys);
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function success(IMessage $message): int
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($message) {
            $result = $redis->evalEx(<<<'LUA'
            -- 从工作队列删除
            redis.call('zrem', KEYS[1], ARGV[1])
            -- 从超时队列删除
            redis.call('del', KEYS[3])
            -- 删除消息
            redis.call('del', KEYS[2] .. ARGV[1])
            return true
            LUA, [
                $this->getQueueKey(QueueType::Working),
                $this->getMessageKeyPrefix(),
                $this->getQueueKey(QueueType::Timeout),
                $message->getMessageId(),
            ], 3);

            if (false === $result)
            {
                if (null === ($error = $redis->getLastError()))
                {
                    throw new QueueException('Queue success failed');
                }
                else
                {
                    throw new QueueException('Queue success failed, ' . $error);
                }
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function fail(IMessage $message, bool $requeue = false): int
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($message, $requeue) {
            $result = $redis->evalEx(<<<'LUA'
            -- 从工作队列删除
            redis.call('zrem', KEYS[1], ARGV[1])
            redis.call('rpush', KEYS[2], ARGV[1])
            return true
            LUA, [
                $this->getQueueKey(QueueType::Working),
                $requeue ? $this->getQueueKey(QueueType::Ready) : $this->getQueueKey(QueueType::Fail),
                $message->getMessageId(),
            ], 2);

            if (false === $result)
            {
                if (null === ($error = $redis->getLastError()))
                {
                    throw new QueueException('Queue success failed');
                }
                else
                {
                    throw new QueueException('Queue success failed, ' . $error);
                }
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function status(): QueueStatus
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) {
            $status = [];
            foreach (QueueType::cases() as $case)
            {
                $count = match ($case->structType())
                {
                    'list'  => $redis->lLen($this->getQueueKey($case)),
                    'zset'  => $redis->zCard($this->getQueueKey($case)),
                    default => throw new QueueException('Invalid type ' . $case->structType()),
                };
                $status[strtolower($case->name)] = $count;
            }

            return new QueueStatus($status);
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreFailMessages(): int
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) {
            $result = $redis->evalEx(<<<'LUA'
            local result = 0
            while(redis.call('Rpoplpush', KEYS[2], KEYS[1]))
            do
                result = result + 1
            end
            return result
            LUA, [
                $this->getQueueKey(QueueType::Ready),
                $this->getQueueKey(QueueType::Fail),
            ], 2);

            if (false === $result)
            {
                if (null === ($error = $redis->getLastError()))
                {
                    throw new QueueException('Queue restoreFailMessages failed');
                }
                else
                {
                    throw new QueueException('Queue restoreFailMessages failed, ' . $error);
                }
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreTimeoutMessages(): int
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) {
            $result = $redis->evalEx(<<<'LUA'
            local result = 0
            while(redis.call('Rpoplpush', KEYS[2], KEYS[1]))
            do
                result = result + 1
            end
            return result
            LUA, [
                $this->getQueueKey(QueueType::Ready),
                $this->getQueueKey(QueueType::Timeout),
            ], 2);

            if (false === $result)
            {
                if (null === ($error = $redis->getLastError()))
                {
                    throw new QueueException('Queue restoreTimeoutMessages failed');
                }
                else
                {
                    throw new QueueException('Queue restoreTimeoutMessages failed, ' . $error);
                }
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * 将达到指定时间的消息加入到队列.
     *
     * 返回消息数量
     */
    protected function parseDelayMessages(\Imi\Redis\RedisHandler $redis, int $count = 100): int
    {
        $result = $redis->evalEx(<<<'LUA'
        -- 查询消息ID
        local messageIds = redis.call('zrevrangebyscore', KEYS[2], ARGV[1], 0, 'limit', 0, ARGV[2])
        local messageIdCount = table.getn(messageIds)
        if 0 == messageIdCount then
            return 0
        end
        -- 加入队列
        redis.call('rpush', KEYS[1], unpack(messageIds))
        -- 从延时队列删除
        redis.call('zrem', KEYS[2], unpack(messageIds))
        return messageIdCount
        LUA, [
            $this->getQueueKey(QueueType::Ready),
            $this->getQueueKey(QueueType::Delay),
            microtime(true),
            $count,
        ], 2);

        if (false === $result)
        {
            if (null === ($error = $redis->getLastError()))
            {
                throw new QueueException('Queue parseDelayMessages failed');
            }
            else
            {
                throw new QueueException('Queue parseDelayMessages failed, ' . $error);
            }
        }

        return $result;
    }

    /**
     * 将处理超时的消息加入到超时队列.
     *
     * 返回消息数量
     */
    protected function parseTimeoutMessages(\Imi\Redis\RedisHandler $redis, int $count = 100): int
    {
        $result = $redis->evalEx(<<<'LUA'
        -- 查询消息ID
        local messageIds = redis.call('zrevrangebyscore', KEYS[1], ARGV[1], 0, 'limit', 0, ARGV[2])
        local messageIdCount = table.getn(messageIds)
        if 0 == messageIdCount then
            return 0
        end
        -- 加入超时队列
        redis.call('rpush', KEYS[2], unpack(messageIds))
        -- 从工作队列删除
        redis.call('zrem', KEYS[1], unpack(messageIds))
        return messageIdCount
        LUA, [
            $this->getQueueKey(QueueType::Working),
            $this->getQueueKey(QueueType::Timeout),
            microtime(true),
            $count,
        ], 2);

        if (false === $result)
        {
            if (null === ($error = $redis->getLastError()))
            {
                throw new QueueException('Queue parseTimeoutMessages failed');
            }
            else
            {
                throw new QueueException('Queue parseTimeoutMessages failed, ' . $error);
            }
        }

        return (int) $result;
    }

    /**
     * 获取消息键前缀
     */
    public function getMessageKeyPrefix(): string
    {
        return $this->prefix . $this->keyName . ':message:';
    }

    /**
     * 获取消息 ID 的键.
     */
    public function getMessageIdKey(): string
    {
        return $this->prefix . $this->keyName . ':message:id';
    }

    /**
     * 获取队列的键.
     */
    public function getQueueKey(int|string|QueueType $queueType): string
    {
        return $this->prefix . $this->keyName . ':' . strtolower($queueType instanceof QueueType ? $queueType->name : (string) $queueType);
    }
}
