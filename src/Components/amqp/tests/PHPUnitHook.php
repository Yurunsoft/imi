<?php

declare(strict_types=1);

namespace Imi\AMQP\Test;

use Imi\App;
use Imi\Cli\CliApp;
use Imi\Swoole\SwooleApp;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;
use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

use function Imi\env;

class PHPUnitHook implements Extension
{
    private Channel $channel;

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class($this->executeBeforeFirstTest(...)) implements ExecutionStartedSubscriber {
            public function __construct(private \Closure $callback)
            {
            }

            public function notify(ExecutionStarted $event): void
            {
                ($this->callback)($event);
            }
        });
        $facade->registerSubscriber(new class($this->executeAfterLastTest(...)) implements ExecutionFinishedSubscriber {
            public function __construct(private \Closure $callback)
            {
            }

            public function notify(ExecutionFinished $event): void
            {
                ($this->callback)($event);
            }
        });
    }

    public function executeBeforeFirstTest(): void
    {
        switch (env('AMQP_TEST_MODE'))
        {
            case 'swoole':
                $this->channel = $channel = new Channel(1);
                Coroutine::create(static function () use ($channel): void {
                    // 要保证连接池连接被释放，必须在当前协程执行完时推给 executeAfterLastTest() 的 pop()
                    Coroutine::defer(static function () use ($channel): void {
                        $channel->push(1);
                    });
                    App::run('AMQPApp', SwooleApp::class, static function () use ($channel): void {
                        $channel->push(1);
                        $channel->pop();
                    });
                });
                $channel->pop();
                break;
            case 'workerman':
                App::run('AMQPApp', CliApp::class, static function (): void {
                });
                break;
            default:
                throw new \RuntimeException('Unsupported AMQP_TEST_MODE');
        }
    }

    public function executeAfterLastTest(): void
    {
        if (isset($this->channel))
        {
            $this->channel->push(1);
            $this->channel->pop();
        }
    }
}
