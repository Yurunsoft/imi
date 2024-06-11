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
use Imi\Redis\Exception\RedisLuaException;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisHandler;
use Imi\Redis\Redis;
use Imi\Redis\RedisLuaScript;
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

    private RedisLuaScript $scriptQueuePushWithDelay;
    private RedisLuaScript $scriptQueuePush;
    private RedisLuaScript $scriptQueuePop;
    private RedisLuaScript $scriptQueueDelete;
    private RedisLuaScript $scriptQueueSuccess;
    private RedisLuaScript $scriptQueueFail;
    private RedisLuaScript $scriptQueueRestoreFail;
    private RedisLuaScript $scriptQueueRestoreTimeout;
    private RedisLuaScript $scriptQueueParseDelayMessages;
    private RedisLuaScript $scriptQueueParseTimeoutMessages;

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
        Redis::use(function (IRedisHandler $redis): void {
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
        return Redis::use(function (IRedisHandler $redis) use ($message, $delay) {
            try
            {
                if ($delay > 0)
                {
                    $args = [
                        microtime(true) + $delay,
                        date('Ymd'),
                    ];
                    foreach ($message->toArray() as $k => $v)
                    {
                        $args[] = $k;
                        $args[] = $v;
                    }
                    $this->scriptQueuePushWithDelay ??= RedisLuaScript::fastCreate(
                        script: <<<'LUA'
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
                        LUA,
                        keyNum: 3,
                    );
                    $result = $this->scriptQueuePushWithDelay->invoke(
                        $redis,
                        [
                            $this->getQueueKey(QueueType::Delay),
                            $this->getMessageKeyPrefix(),
                            $this->getMessageIdKey(),
                        ],
                        ...$args,
                    );
                }
                else
                {
                    $args = [
                        date('Ymd'),
                    ];
                    foreach ($message->toArray() as $k => $v)
                    {
                        $args[] = $k;
                        $args[] = $v;
                    }
                    $this->scriptQueuePush ??= RedisLuaScript::fastCreate(
                        script: <<<'LUA'
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
                        LUA,
                        keyNum: 3,
                    );

                    $result = $this->scriptQueuePush->invoke(
                        $redis,
                        [
                            $this->getQueueKey(QueueType::Ready),
                            $this->getMessageKeyPrefix(),
                            $this->getMessageIdKey(),
                        ],
                        ...$args,
                    );
                }
            }
            catch (RedisLuaException $exception)
            {
                throw new QueueException('Queue push failed: ' . $exception->getMessage(), previous: $exception);
            }
            if (false === $result)
            {
                throw new QueueException('Queue push failed');
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
            $result = Redis::use(function (IRedisHandler $redis) {
                $this->parseDelayMessages($redis);
                $this->parseTimeoutMessages($redis);

                $this->scriptQueuePop ??= RedisLuaScript::fastCreate(
                    script: <<<'LUA'
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
                    LUA,
                    keyNum: 3,
                );

                try
                {
                    $result = $this->scriptQueuePop->invoke(
                        $redis,
                        [
                            $this->getQueueKey(QueueType::Ready),
                            $this->getQueueKey(QueueType::Working),
                            $this->getMessageKeyPrefix(),
                        ],
                        microtime(true),
                    );
                }
                catch (RedisLuaException $exception)
                {
                    throw new QueueException('Queue push failed: ' . $exception->getMessage(), previous: $exception);
                }
                if (false === $result)
                {
                    throw new QueueException('Queue push failed');
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
        return Redis::use(function (IRedisHandler $redis) use ($message) {
            $this->scriptQueueDelete ??= RedisLuaScript::fastCreate(
                script: <<<'LUA'
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
                LUA,
                keyNum: 3,
            );

            try
            {
                $result = $this->scriptQueueDelete->invoke(
                    $redis,
                    [
                        $this->getQueueKey(QueueType::Ready),
                        $this->getQueueKey(QueueType::Delay),
                        $this->getMessageKeyPrefix(),
                    ],
                    $message->getMessageId(),
                );
            }
            catch (RedisLuaException $exception)
            {
                throw new QueueException('Queue delete failed, ' . $exception->getMessage(), previous: $exception);
            }
            if (false === $result)
            {
                return false;
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

        Redis::use(static function (IRedisHandler $redis) use ($keys): void {
            /** @var PhpRedisHandler|PredisHandler $redis */
            $redis->del(...$keys);
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function success(IMessage $message): int
    {
        return Redis::use(function (IRedisHandler $redis) use ($message) {
            $this->scriptQueueSuccess ??= RedisLuaScript::fastCreate(
                script: <<<'LUA'
                -- 从工作队列删除
                redis.call('zrem', KEYS[1], ARGV[1])
                -- 从超时队列删除
                redis.call('del', KEYS[3])
                -- 删除消息
                redis.call('del', KEYS[2] .. ARGV[1])
                return true
                LUA,
                keyNum: 3,
            );
            try
            {
                $result = $this->scriptQueueSuccess->invoke($redis, [
                    $this->getQueueKey(QueueType::Working),
                    $this->getMessageKeyPrefix(),
                    $this->getQueueKey(QueueType::Timeout),
                ], $message->getMessageId());
            }
            catch (RedisLuaException $exception)
            {
                throw new QueueException('Queue success failed, ' . $exception->getMessage(), previous: $exception);
            }
            if (false === $result)
            {
                throw new QueueException('Queue success failed');
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function fail(IMessage $message, bool $requeue = false): int
    {
        return Redis::use(function (IRedisHandler $redis) use ($message, $requeue) {
            $this->scriptQueueFail ??= RedisLuaScript::fastCreate(
                script: <<<'LUA'
                -- 从工作队列删除
                redis.call('zrem', KEYS[1], ARGV[1])
                redis.call('rpush', KEYS[2], ARGV[1])
                return true
                LUA,
                keyNum: 2,
            );
            try
            {
                $result = $this->scriptQueueFail->invoke(
                    $redis,
                    [
                        $this->getQueueKey(QueueType::Working),
                        $requeue ? $this->getQueueKey(QueueType::Ready) : $this->getQueueKey(QueueType::Fail),
                    ],
                    $message->getMessageId(),
                );
            }
            catch (RedisLuaException $exception)
            {
                throw new QueueException('Queue success failed, ' . $exception->getMessage(), previous: $exception);
            }
            if (false === $result)
            {
                throw new QueueException('Queue success failed');
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function status(): QueueStatus
    {
        return Redis::use(function (IRedisHandler $redis) {
            /** @var PhpRedisHandler|PredisHandler $redis */
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
        return Redis::use(function (IRedisHandler $redis) {
            $this->scriptQueueRestoreFail ??= RedisLuaScript::fastCreate(
                script: <<<'LUA'
                local result = 0
                while(redis.call('Rpoplpush', KEYS[2], KEYS[1]))
                do
                    result = result + 1
                end
                return result
                LUA,
                keyNum: 2,
            );
            try
            {
                $result = $this->scriptQueueRestoreFail->invoke($redis, [
                    $this->getQueueKey(QueueType::Ready),
                    $this->getQueueKey(QueueType::Fail),
                ]);
            }
            catch (RedisLuaException $exception)
            {
                throw new QueueException('Queue restoreFailMessages failed, ' . $exception->getMessage(), previous: $exception);
            }
            if (false === $result)
            {
                throw new QueueException('Queue restoreFailMessages failed');
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreTimeoutMessages(): int
    {
        return Redis::use(function (IRedisHandler $redis) {
            $this->scriptQueueRestoreTimeout ??= RedisLuaScript::fastCreate(
                script: <<<'LUA'
                local result = 0
                while(redis.call('Rpoplpush', KEYS[2], KEYS[1]))
                do
                    result = result + 1
                end
                return result
                LUA,
                keyNum: 2,
            );
            try
            {
                $result = $this->scriptQueueRestoreTimeout->invoke($redis, [
                    $this->getQueueKey(QueueType::Ready),
                    $this->getQueueKey(QueueType::Timeout),
                ]);
            }
            catch (RedisLuaException $exception)
            {
                throw new QueueException('Queue restoreTimeoutMessages failed, ' . $exception->getMessage(), previous: $exception);
            }
            if (false === $result)
            {
                throw new QueueException('Queue restoreTimeoutMessages failed');
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * 将达到指定时间的消息加入到队列.
     *
     * 返回消息数量
     */
    protected function parseDelayMessages(IRedisHandler $redis, int $count = 100): int
    {
        $this->scriptQueueParseDelayMessages ??= RedisLuaScript::fastCreate(
            script: <<<'LUA'
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
            LUA,
            keyNum: 2,
        );
        try
        {
            $result = $this->scriptQueueParseDelayMessages->invoke(
                $redis,
                [
                    $this->getQueueKey(QueueType::Ready),
                    $this->getQueueKey(QueueType::Delay),
                ],
                microtime(true),
                $count,
            );
        }
        catch (RedisLuaException $exception)
        {
            throw new QueueException('Queue parseDelayMessages failed, ' . $exception->getMessage(), previous: $exception);
        }
        if (false === $result)
        {
            throw new QueueException('Queue parseDelayMessages failed');
        }

        return $result;
    }

    /**
     * 将处理超时的消息加入到超时队列.
     *
     * 返回消息数量
     */
    protected function parseTimeoutMessages(IRedisHandler $redis, int $count = 100): int
    {
        $this->scriptQueueParseTimeoutMessages ??= RedisLuaScript::fastCreate(
            script: <<<'LUA'
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
            LUA,
            keyNum: 2,
        );
        try
        {
            $result = $this->scriptQueueParseTimeoutMessages->invoke(
                $redis,
                [
                    $this->getQueueKey(QueueType::Working),
                    $this->getQueueKey(QueueType::Timeout),
                ],
                microtime(true),
                $count,
            );
        }
        catch (RedisLuaException $exception)
        {
            throw new QueueException('Queue parseTimeoutMessages failed, ' . $exception->getMessage(), previous: $exception);
        }

        if (false === $result)
        {
            throw new QueueException('Queue parseTimeoutMessages failed');
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
