<?php

declare(strict_types=1);

namespace Imi\AMQP\Queue;

use Imi\AMQP\Contract\IQueueConsumer;
use Imi\Bean\BeanFactory;
use Imi\Queue\Contract\IMessage;
use Imi\Queue\Driver\IQueueDriver;
use Imi\Queue\Enum\IQueueType;
use Imi\Queue\Enum\QueueType;
use Imi\Queue\Exception\QueueException;
use Imi\Queue\Model\QueueStatus;
use Imi\Redis\Redis;
use Imi\Util\Imi;
use Imi\Util\Traits\TDataToProperty;

/**
 * AMQP 队列驱动.
 */
class AMQPQueueDriverHandler implements IQueueDriver
{
    use TDataToProperty{
        __construct as private traitConstruct;
    }

    /**
     * AMQP 连接池名称.
     */
    protected ?string $poolName = null;

    /**
     * 支持消息删除功能.
     *
     * 依赖 Redis
     */
    protected bool $supportDelete = true;

    /**
     * 支持消费超时队列功能.
     *
     * 依赖 Redis，并且自动增加一个队列
     */
    protected bool $supportTimeout = true;

    /**
     * 支持消费失败队列功能.
     *
     * 自动增加一个队列
     */
    protected bool $supportFail = true;

    /**
     * Redis 连接池名称.
     */
    protected ?string $redisPoolName = null;

    /**
     * Redis 键名前缀
     */
    protected string $redisPrefix = '';

    /**
     * 循环尝试 pop 的时间间隔，单位：秒.
     */
    protected float $timespan = 0.03;

    /**
     * 本地缓存的队列长度.
     */
    protected int $queueLength = 16;

    /**
     * 消息类名.
     */
    protected string $message = JsonAMQPMessage::class;

    /**
     * 发布者.
     */
    private ?QueuePublisher $publisher = null;

    /**
     * 延迟发布者.
     */
    private ?QueuePublisher $delayPublisher = null;

    /**
     * 消费者.
     */
    private ?IQueueConsumer $consumer = null;

    /**
     * 超时队列发布者.
     */
    private ?QueuePublisher $timeoutPublisher = null;

    /**
     * 超时队列消费者.
     */
    private ?IQueueConsumer $timeoutConsumer = null;

    /**
     * 失败队列发布者.
     */
    private ?QueuePublisher $failPublisher = null;

    /**
     * 失败队列消费者.
     */
    private ?IQueueConsumer $failConsumer = null;

    /**
     * AMQP 的队列名称.
     */
    private string $queueName = '';

    /**
     * AMQP 的延迟队列名称.
     */
    private string $delayQueueName = '';

    /**
     * AMQP 的失败队列名称.
     */
    private string $failQueueName = '';

    /**
     * AMQP 的超时队列名称.
     */
    private string $timeoutQueueName = '';

    public function __construct(
        /**
         * 队列名称.
         */
        protected string $name, array $config = [])
    {
        $this->traitConstruct($config);

        $exchangeName = 'imi-' . $name;
        $this->queueName = $queueName = 'imi-queue-' . $name;
        $this->delayQueueName = $queueDelayName = 'imi-' . $name . '-delay';
        $exchanges = [
            [
                'name'  => $exchangeName,
            ],
        ];
        $queues = [
            [
                'name'          => $queueName,
                'routingKey'    => AMQPQueueDriver::ROUTING_NORMAL,
            ],
        ];
        $publishers = [
            [
                'exchange'      => $exchangeName,
                'routingKey'    => AMQPQueueDriver::ROUTING_NORMAL,
                'queue'         => $queueName,
            ],
        ];
        $consumers = [
            [
                'exchange'      => $exchangeName,
                'queue'         => $queueName,
                'routingKey'    => AMQPQueueDriver::ROUTING_NORMAL,
                'message'       => $this->message,
            ],
        ];
        $delayQueues = [
            [
                'name'          => $queueDelayName,
                'arguments'     => [
                    'x-dead-letter-exchange'    => $exchangeName,
                    'x-dead-letter-routing-key' => AMQPQueueDriver::ROUTING_NORMAL,
                ],
                'routingKey'    => AMQPQueueDriver::ROUTING_DELAY,
            ],
        ];
        $delayPublishers = [
            [
                'exchange'   => $exchangeName,
                'routingKey' => AMQPQueueDriver::ROUTING_DELAY,
                'queue'      => $queueDelayName,
            ],
        ];
        $this->publisher = BeanFactory::newInstance(QueuePublisher::class, $exchanges, $queues, $publishers, $this->poolName);
        $this->delayPublisher = BeanFactory::newInstance(QueuePublisher::class, $exchanges, $delayQueues, $delayPublishers, $this->poolName);
        $consumerClass = Imi::checkAppType('swoole') ? SwooleQueueConsumer::class : QueueConsumer::class;
        $this->consumer = BeanFactory::newInstance($consumerClass, $this->queueLength, $exchanges, $queues, $consumers, $this->poolName);
        if ($this->supportTimeout)
        {
            $this->timeoutQueueName = $timeoutQueueName = ('imi-' . $name . '-timeout');
            $this->timeoutPublisher = BeanFactory::newInstance(QueuePublisher::class, $exchanges, [
                [
                    'name'          => $timeoutQueueName,
                    'routingKey'    => AMQPQueueDriver::ROUTING_TIMEOUT,
                ],
            ], [
                [
                    'exchange'      => $exchangeName,
                    'routingKey'    => AMQPQueueDriver::ROUTING_TIMEOUT,
                    'queue'         => $timeoutQueueName,
                ],
            ], $this->poolName);
            $this->timeoutConsumer = BeanFactory::newInstance($consumerClass, 1, $exchanges, [
                [
                    'name'          => $timeoutQueueName,
                    'routingKey'    => AMQPQueueDriver::ROUTING_TIMEOUT,
                ],
            ], [
                [
                    'exchange'      => $exchangeName,
                    'routingKey'    => AMQPQueueDriver::ROUTING_TIMEOUT,
                    'queue'         => $timeoutQueueName,
                    'message'       => $this->message,
                ],
            ], $this->poolName);
        }
        if ($this->supportFail)
        {
            $this->failQueueName = $failQueueName = ('imi-' . $name . '-fail');
            $this->failPublisher = BeanFactory::newInstance(QueuePublisher::class, $exchanges, [
                [
                    'name'          => $failQueueName,
                    'routingKey'    => AMQPQueueDriver::ROUTING_FAIL,
                ],
            ], [
                [
                    'exchange'      => $exchangeName,
                    'routingKey'    => AMQPQueueDriver::ROUTING_FAIL,
                    'queue'         => $failQueueName,
                ],
            ], $this->poolName);
            $this->failConsumer = BeanFactory::newInstance($consumerClass, 1, $exchanges, [
                [
                    'name'          => $failQueueName,
                    'routingKey'    => AMQPQueueDriver::ROUTING_FAIL,
                ],
            ], [
                [
                    'exchange'      => $exchangeName,
                    'routingKey'    => AMQPQueueDriver::ROUTING_FAIL,
                    'queue'         => $failQueueName,
                    'message'       => $this->message,
                ],
            ], $this->poolName);
        }
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
            $message->setMessageId($messageId = (string) $redis->incr($this->getRedisMessageIdKey()));
            $amqpMessage = new \Imi\AMQP\Message();
            $amqpMessage->setBody(json_encode($message->toArray(), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
            if ($delay > 0)
            {
                $amqpMessage->setRoutingKey(AMQPQueueDriver::ROUTING_DELAY);
                $amqpMessage->setProperties([
                    'expiration'    => $delay * 1000,
                ]);
                $this->delayPublisher->publish($amqpMessage);
            }
            else
            {
                $amqpMessage->setRoutingKey(AMQPQueueDriver::ROUTING_NORMAL);
                $this->publisher->publish($amqpMessage);
            }

            return $messageId;
        }, $this->redisPoolName);
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
            if ($this->supportTimeout)
            {
                $this->parseTimeoutMessages();
            }
            $result = $this->consumer->pop($this->timespan);
            if ($result)
            {
                $message = new QueueAMQPMessage();
                $message->setAmqpMessage($result);
                // 检查是否被删除
                if ($this->messageIsDeleted($message->getMessageId()))
                {
                    $this->success($message);
                    continue;
                }
                // 加入工作队列
                $workingTimeout = $message->getWorkingTimeout();
                if ($workingTimeout > 0)
                {
                    $score = microtime(true) + $workingTimeout;
                }
                else
                {
                    $score = -1;
                }

                Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($score, $message): void {
                    $redis->zAdd($this->getRedisQueueKey(QueueType::Working), $score, json_encode($message->toArray(), \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
                }, $this->redisPoolName);

                return $message;
            }
            elseif ($timeout < 0)
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
        return Redis::use(fn (\Imi\Redis\RedisHandler $redis) => $redis->sAdd($this->getRedisQueueKey('deleted'), $message->getMessageId()) > 0, $this->redisPoolName);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(?IQueueType $queueType = null): void
    {
        Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($queueType): void {
            if (null === $queueType)
            {
                $queueTypes = QueueType::cases();
            }
            else
            {
                $queueTypes = (array) $queueType;
            }
            foreach ($queueTypes as $queueType)
            {
                try
                {
                    switch ($queueType)
                    {
                        case QueueType::Ready:
                            // 清空所有
                            while ($message = $this->pop())
                            {
                                // @phpstan-ignore-next-line
                                $this->success($message);
                            }
                            $this->consumer->getAMQPChannel()->queue_purge($this->queueName);
                            $this->consumer->reopen();
                            $redis->del($this->getRedisQueueKey('deleted'));
                            break;
                        case QueueType::Working:
                            $redis->del($this->getRedisQueueKey(QueueType::Working));
                            break;
                        case QueueType::Fail:
                            $this->failConsumer->getAMQPChannel()->queue_purge($this->failQueueName);
                            $this->failConsumer->reopen();
                            break;
                        case QueueType::Timeout:
                            $this->timeoutConsumer->getAMQPChannel()->queue_purge($this->timeoutQueueName);
                            $this->timeoutConsumer->reopen();
                            break;
                        case QueueType::Delay:
                            $this->delayPublisher->getAMQPChannel()->queue_purge($this->delayQueueName);
                            break;
                    }
                }
                catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException)
                {
                }
            }
        }, $this->redisPoolName);
    }

    /**
     * {@inheritDoc}
     *
     * @param \Imi\AMQP\Queue\QueueAMQPMessage $message
     */
    public function success(IMessage $message): int
    {
        $this->consumer->getAMQPChannel()->basic_ack($message->getAmqpMessage()->getAMQPMessage()->getDeliveryTag());

        return 1;
    }

    /**
     * {@inheritDoc}
     *
     * @param \Imi\AMQP\Queue\QueueAMQPMessage $message
     */
    public function fail(IMessage $message, bool $requeue = false): int
    {
        if ($requeue)
        {
            $this->consumer->getAMQPChannel()->basic_nack($message->getAmqpMessage()->getAMQPMessage()->getDeliveryTag(), false, $requeue);

            return 0;
        }
        if ($this->supportFail)
        {
            $amqpMessage = $message->getAmqpMessage();
            $amqpMessage->setRoutingKey(AMQPQueueDriver::ROUTING_FAIL);
            $this->failPublisher->publish($amqpMessage);
            $this->consumer->getAMQPChannel()->basic_ack($message->getAmqpMessage()->getAMQPMessage()->getDeliveryTag());
        }
        else
        {
            $this->consumer->getAMQPChannel()->basic_nack($message->getAmqpMessage()->getAMQPMessage()->getDeliveryTag());
        }

        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function status(): QueueStatus
    {
        return Redis::use(function (\Imi\Redis\RedisHandler $redis) {
            $status = [];

            // ready
            try
            {
                $result = $this->consumer->getAMQPChannel()->queue_declare($this->queueName, true, false, false, false);
                if (!$result)
                {
                    throw new \RuntimeException(sprintf('Get queue:%s info failed', $this->queueName));
                }
                $ready = (int) $result[1];
            }
            catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException)
            {
                $ready = 0;
            }
            $status['ready'] = $ready;

            // working
            $status['working'] = $redis->zCard($this->getRedisQueueKey(QueueType::Working));

            // fail
            $fail = 0;
            try
            {
                if ($this->supportFail)
                {
                    $result = $this->consumer->getAMQPChannel()->queue_declare($this->failQueueName, true, false, false, false);
                    if (!$result)
                    {
                        throw new \RuntimeException(sprintf('Get queue:%s info failed', $this->failQueueName));
                    }
                    [, $failReady] = $result;
                    $fail += $failReady;
                }
            }
            catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException)
            {
            }
            $status['fail'] = $fail;
            // timeout
            if ($this->supportTimeout)
            {
                try
                {
                    $result = $this->consumer->getAMQPChannel()->queue_declare($this->timeoutQueueName, true, false, false, false);
                    if (!$result)
                    {
                        throw new \RuntimeException(sprintf('Get queue:%s info failed', $this->timeoutQueueName));
                    }
                    [, $timeoutReady] = $result;
                    $status['timeout'] = $timeoutReady;
                }
                catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException)
                {
                    $status['timeout'] = 0;
                }
            }
            else
            {
                $status['timeout'] = 0;
            }

            // delay
            try
            {
                $result = $this->consumer->getAMQPChannel()->queue_declare($this->delayQueueName, true, false, false, false);
                if (!$result)
                {
                    throw new \RuntimeException(sprintf('Get queue:%s info failed', $this->delayQueueName));
                }
                [, $delayReady] = $result;
            }
            catch (\PhpAmqpLib\Exception\AMQPProtocolChannelException)
            {
                $delayReady = 0;
            }
            $status['delay'] = $delayReady;

            return new QueueStatus($status);
        }, $this->redisPoolName);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreFailMessages(): int
    {
        $count = 0;
        while ($message = $this->failConsumer->pop(0.001))
        {
            $amqpMessage = new \Imi\AMQP\Message();
            $amqpMessage->setBody($message->getBody());
            $amqpMessage->setRoutingKey(AMQPQueueDriver::ROUTING_NORMAL);
            $this->publisher->publish($amqpMessage);
            $this->failConsumer->getAMQPChannel()->basic_ack($message->getAMQPMessage()->getDeliveryTag());
            ++$count;
        }
        $this->failConsumer->reopen();

        return $count;
    }

    /**
     * {@inheritDoc}
     */
    public function restoreTimeoutMessages(): int
    {
        $count = 0;
        while ($message = $this->timeoutConsumer->pop(0.001))
        {
            $amqpMessage = new \Imi\AMQP\Message();
            $amqpMessage->setBody($message->getBody());
            $amqpMessage->setRoutingKey(AMQPQueueDriver::ROUTING_NORMAL);
            $this->publisher->publish($amqpMessage);
            $this->timeoutConsumer->getAMQPChannel()->basic_ack($message->getAMQPMessage()->getDeliveryTag());
            ++$count;
        }
        $this->timeoutConsumer->reopen();

        return $count;
    }

    /**
     * 获取消息 ID 的键.
     */
    public function getRedisMessageIdKey(): string
    {
        return $this->redisPrefix . $this->name . ':message:id';
    }

    /**
     * 获取队列的键.
     */
    public function getRedisQueueKey(string|IQueueType $queueType): string
    {
        return $this->redisPrefix . $this->name . ':' . strtolower($queueType->name ?? $queueType);
    }

    /**
     * 将处理超时的消息加入到超时队列.
     *
     * 返回消息数量
     */
    protected function parseTimeoutMessages(int $count = 100): void
    {
        Redis::use(function (\Imi\Redis\RedisHandler $redis) use ($count): void {
            $result = $redis->evalEx(<<<'LUA'
            -- 查询消息ID
            local messages = redis.call('zrevrangebyscore', KEYS[1], ARGV[1], 0, 'limit', 0, ARGV[2])
            local messageIdCount = table.getn(messages)
            if 0 == messageIdCount then
                return 0
            end
            -- 从工作队列删除
            redis.call('zrem', KEYS[1], unpack(messages))
            return messages
            LUA, [
                $this->getRedisQueueKey(QueueType::Working),
                microtime(true),
                $count,
            ], 1);

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

            foreach ($result ?: [] as $message)
            {
                $amqpMessage = new \Imi\AMQP\Message();
                $amqpMessage->setBody($redis->_unserialize($message));
                $amqpMessage->setRoutingKey(AMQPQueueDriver::ROUTING_TIMEOUT);
                $this->timeoutPublisher->publish($amqpMessage);
            }
        }, $this->redisPoolName);
    }

    /**
     * 消息是否被删除.
     */
    protected function messageIsDeleted(string $messageId, bool $delete = true): bool
    {
        return Redis::use(fn (\Imi\Redis\RedisHandler $redis) => $redis->evalEx(<<<'LUA'
        local deletedKey = KEYS[1];
        local messageId = ARGV[1];
        local deleteRecord = ARGV[2];
        if(deleteRecord)
        then
            return redis.call('srem', deletedKey, messageId);
        else
            return redis.call('sismember', deletedKey, messageId);
        end
        LUA, [
            $this->getRedisQueueKey('deleted'),
            $redis->_serialize($messageId),
            $delete,
        ], 1) > 0, $this->redisPoolName);
    }
}
