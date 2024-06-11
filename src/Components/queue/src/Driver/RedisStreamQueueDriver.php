<?php

declare(strict_types=1);

namespace Imi\Queue\Driver;

use Imi\Bean\Annotation\Bean;
use Imi\Queue\Contract\IMessage;
use Imi\Queue\Contract\IRedisStreamMessage;
use Imi\Queue\Enum\IQueueType;
use Imi\Queue\Exception\QueueException;
use Imi\Queue\Model\QueueStatus;
use Imi\Queue\Model\RedisStreamMessage;
use Imi\Redis\Redis;
use Imi\Util\Text;
use Imi\Util\Traits\TDataToProperty;

/**
 * Redis Stream 队列驱动.
 * 要求 Redis >= 5.0.
 */
#[Bean(name: 'RedisStreamQueueDriver')]
class RedisStreamQueueDriver implements IQueueDriver
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
     * 队列最大长度.
     *
     * 为0则不限制
     */
    protected int $maxLength = 0;

    /**
     * 队列最大长度近似模式.
     */
    protected bool $approximate = false;

    /**
     * 分组ID.
     */
    protected string $groupId = 'imiGroup';

    /**
     * 队列消费者.
     */
    protected string $queueConsumer = 'imiQueueConsumer';

    /**
     * 消费失败后消息转移的目标消费者.
     */
    protected string $failConsumer = 'imiFailConsumer';

    /**
     * 工作超时时间.
     */
    protected float $workingTimeout = 60;

    /**
     * 分组是否初始化过.
     */
    private bool $groupInited = false;

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
        if ($delay > 0)
        {
            throw new \RuntimeException('Unsupport delay push in RedisStreamQueueDriver');
        }
        if ($message instanceof IRedisStreamMessage)
        {
            $fields = $message->getArrayMessage();
        }
        else
        {
            $fields = [
                'message' => $message->getMessage(),
            ];
        }

        return Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($message, $fields) {
            $messageId = $message->getMessageId();
            $result = $redis->xadd($this->getQueueKey(), Text::isEmpty($messageId) ? '*' : $messageId, $fields, $this->maxLength, $this->approximate);
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
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($timeout) {
            $this->prepareGroup($redis);
            $queueKey = $this->getQueueKey();
            $result = $redis->xreadgroup($this->groupId, $this->queueConsumer, [$queueKey => '>'], 1, $timeout > 0 ? (int) ($timeout * 1000) : null);
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
            if (!$result)
            {
                return null;
            }
            $message = new RedisStreamMessage();
            $messageId = array_key_first($result[$queueKey]);
            $data = $result[$queueKey][$messageId];
            $data['messageId'] = $messageId;
            $message->loadFromArray($data);

            return $message;
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(IMessage $message): bool
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($message) {
            $result = $redis->xdel($this->getQueueKey(), [$message->getMessageId()]);

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
        Redis::use(function (\Imi\Redis\RedisHandler $redis): void {
            $redis->del($this->getQueueKey());
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function success(IMessage $message): int
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($message) {
            $this->prepareGroup($redis);
            $queueKey = $this->getQueueKey();
            $result = $redis->xack($queueKey, $this->groupId, [$message->getMessageId()]);

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
            $this->prepareGroup($redis);
            $queueKey = $this->getQueueKey();
            if ($requeue)
            {
                $messageId = $message->getMessageId();
                $message->setMessageId('');
                $this->push($message);
                $redis->xack($queueKey, $this->groupId, [$messageId]);

                return 1;
            }
            else
            {
                $result = $redis->xclaim($queueKey, $this->groupId, $this->failConsumer, 0, [$message->getMessageId()], [
                    'IDLE'   => 0,
                    'FORCE',
                    'JUSTID',
                ]);
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

                return $result ? 1 : 0;
            }
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function status(): QueueStatus
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) {
            $status = [];
            $queueKey = $this->getQueueKey();
            $info = $redis->xinfo('STREAM', $queueKey, 'FULL', 1);
            if (false === $info)
            {
                if (null === ($error = $redis->getLastError()))
                {
                    throw new QueueException('Queue success failed');
                }
                elseif ('ERR no such key' === $error)
                {
                    $info = [];
                }
                else
                {
                    throw new QueueException('Queue success failed, ' . $error);
                }
            }
            $groupInfo = null;
            $groupId = $this->groupId;
            foreach ($info['groups'] ?? [] as $group)
            {
                if ($group['name'] === $groupId)
                {
                    $groupInfo = $group;
                    break;
                }
            }
            $failConsumerInfo = null;
            if ($groupInfo)
            {
                $failConsumer = $this->failConsumer;
                foreach ($groupInfo['consumers'] ?? [] as $consumer)
                {
                    if ($consumer['name'] === $failConsumer)
                    {
                        $failConsumerInfo = $consumer;
                        break;
                    }
                }
            }
            $status['fail'] = $failConsumerInfo['pel-count'] ?? 0;
            $status['working'] = ($groupInfo['pel-count'] ?? 0) - $status['fail'];
            $status['ready'] = $status['timeout'] = $status['delay'] = 0;

            return new QueueStatus($status);
        }, $this->poolName);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreFailMessages(): int
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) {
            $queueKey = $this->getQueueKey();
            $result = 0;
            $groupId = $this->groupId;
            $failConsumer = $this->failConsumer;
            $maxLength = $this->maxLength;
            $approximate = $this->approximate;
            $start = '0';
            while (true)
            {
                $xreadgroupResult = $redis->xreadgroup($groupId, $failConsumer, [$queueKey => $start], 100);
                if (false === $xreadgroupResult)
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
                if (!$xreadgroupResult)
                {
                    break;
                }
                $ids = [];
                foreach ($xreadgroupResult[$queueKey] as $messageId => $data)
                {
                    if ($start === $messageId)
                    {
                        continue;
                    }
                    $ids[] = $start = $messageId;
                    $redis->xadd($queueKey, '*', $data, $maxLength, $approximate);
                    ++$result;
                }
                if ($ids)
                {
                    $redis->xack($queueKey, $groupId, $ids);
                }
                else
                {
                    break;
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
            $queueKey = $this->getQueueKey();
            $result = 0;
            $groupId = $this->groupId;
            $queueConsumer = $this->queueConsumer;
            $maxLength = $this->maxLength;
            $approximate = $this->approximate;
            $workingTimeoutMs = (int) ($this->workingTimeout * 1000);
            $start = '-';
            while (true)
            {
                $xpendingResult = $redis->xpending($queueKey, $groupId, $start, '+', 100, $queueConsumer);
                if (false === $xpendingResult)
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
                if (!$xpendingResult)
                {
                    break;
                }
                $ids = [];
                foreach ($xpendingResult as $data)
                {
                    $id = $data[0];
                    if ($id === $start || $data[2] < $workingTimeoutMs)
                    {
                        // 判断超时
                        continue;
                    }
                    $start = $id;
                    $xrangeResult = $redis->xrange($queueKey, $id, $id, 1);
                    if (false === $xrangeResult)
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
                    if ($xrangeResult)
                    {
                        $data = $xrangeResult[$id];
                        $redis->xadd($queueKey, '*', $data, $maxLength, $approximate);
                        ++$result;
                        $ids[] = $id;
                    }
                }
                if ($ids)
                {
                    $redis->xack($queueKey, $groupId, $ids);
                }
                else
                {
                    break;
                }
            }

            return $result;
        }, $this->poolName);
    }

    /**
     * 获取队列的键.
     */
    public function getQueueKey(): string
    {
        return $this->prefix . $this->keyName;
    }

    protected function prepareGroup(\Imi\Redis\RedisHandler $redis): void
    {
        if (!$this->groupInited)
        {
            $queueKey = $this->getQueueKey();
            $redis->xgroup('create', $queueKey, $this->groupId, '0', true);
            $redis->clearLastError();
        }
    }
}
