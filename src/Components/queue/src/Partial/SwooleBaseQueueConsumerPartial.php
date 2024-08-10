<?php

declare(strict_types=1);

namespace Imi\Queue\Partial;

use Imi\Bean\Annotation\Partial;
use Imi\Event\Event;
use Imi\Log\Log;
use Imi\Queue\Event\Param\ConsumerAfterConsumeParam;
use Imi\Queue\Event\Param\ConsumerAfterPopParam;
use Imi\Queue\Event\Param\ConsumerBeforeConsumeParam;
use Imi\Queue\Event\Param\ConsumerBeforePopParam;
use Imi\Queue\Model\QueueConfig;
use Imi\RequestContext;
use Swoole\Coroutine;
use Yurun\Swoole\CoPool\CoPool;
use Yurun\Swoole\CoPool\Interfaces\ICoTask;
use Yurun\Swoole\CoPool\Interfaces\ITaskParam;

use function Yurun\Swoole\Coroutine\goWait;

if (\Imi\Util\Imi::checkAppType('swoole'))
{
    /**
     * @Partial(\Imi\Queue\Service\BaseQueueConsumer::class)
     *
     * @property \Imi\Queue\Service\QueueService $imiQueue
     */
    trait SwooleBaseQueueConsumerPartial
    {
        /**
         * 协程工作池.
         */
        private ?CoPool $coPool = null;

        /**
         * 开始消费循环.
         */
        public function start(?int $co = null): void
        {
            $this->working = true;
            $config = $this->imiQueue->getQueueConfig($this->name);
            if (null === $co)
            {
                $co = $config->getCo();
            }
            $task = function () use ($config) {
                while ($this->working)
                {
                    try
                    {
                        goWait(fn () => $this->task($config), -1, true);
                    }
                    catch (\Throwable $th)
                    {
                        Log::error($th);
                    }
                }
            };
            if ($co > 0)
            {
                // @phpstan-ignore-next-line
                $this->coPool = $pool = new CoPool($co, $co, new class() implements ICoTask {
                    /**
                     * {@inheritDoc}
                     */
                    public function run(ITaskParam $param)
                    {
                        ($param->getData()['task'])();
                    }
                });
                $pool->run();
                for ($i = 0; $i < $co; ++$i)
                {
                    $pool->addTaskAsync([
                        'task'  => $task,
                    ]);
                }
                $pool->wait();
            }
            else
            {
                $task();
            }
        }

        /**
         * 停止消费.
         */
        public function stop(): void
        {
            if ($this->working)
            {
                $this->coPool->stop();
            }
            $this->working = false;
        }

        protected function task(QueueConfig $config): void
        {
            $queue = $this->imiQueue->getQueue($this->name);
            do
            {
                Event::trigger('IMI.QUEUE.CONSUMER.BEFORE_POP', [
                    'queue' => $queue,
                ], $this, ConsumerBeforePopParam::class);
                $message = $queue->pop();
                Event::trigger('IMI.QUEUE.CONSUMER.AFTER_POP', [
                    'queue'     => $queue,
                    'message'   => $message,
                ], $this, ConsumerAfterPopParam::class);
                if (null === $message)
                {
                    Coroutine::sleep($config->getTimespan());
                }
                else
                {
                    $context = RequestContext::getContext();
                    $handlerName = 'QueueDriver.handler.' . $queue->getName();
                    $handler = $context[$handlerName] ?? null;
                    goWait(function () use ($queue, $message, $handlerName, $handler) {
                        RequestContext::set($handlerName, $handler);
                        Event::trigger('IMI.QUEUE.CONSUMER.BEFORE_CONSUME', [
                            'queue'     => $queue,
                            'message'   => $message,
                        ], $this, ConsumerBeforeConsumeParam::class);
                        $this->consume($message, $queue);
                        Event::trigger('IMI.QUEUE.CONSUMER.AFTER_CONSUME', [
                            'queue'     => $queue,
                            'message'   => $message,
                        ], $this, ConsumerAfterConsumeParam::class);
                    }, -1, true);
                }
            }
            while ($this->working);
        }
    }
}
