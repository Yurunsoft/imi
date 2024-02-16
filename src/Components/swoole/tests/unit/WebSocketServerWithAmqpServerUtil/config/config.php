<?php

declare(strict_types=1);

use function Imi\env;

\defined('AMQP_SERVER_HOST') || \define('AMQP_SERVER_HOST', env('AMQP_SERVER_HOST', '127.0.0.1'));

return [
    // 配置文件
    'configs'           => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 扫描目录
    'beanScan'          => [
        'Imi\Swoole\Test\WebSocketServerWithAmqpServerUtil\Listener',
    ],

    // 组件命名空间
    'components'        => [
        'Swoole' => 'Imi\Swoole',
        'AMQP'   => 'Imi\AMQP',
        'Macro'  => 'Imi\Macro',
    ],

    // 日志配置
    'logger'            => [
        'channels' => [
            'imi' => [
                'handlers' => [
                    [
                        'class'     => \Imi\Log\Handler\ConsoleHandler::class,
                        'formatter' => [
                            'class'     => \Imi\Log\Formatter\ConsoleLineFormatter::class,
                            'construct' => [
                                'format'                     => null,
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                    [
                        'class'     => \Monolog\Handler\RotatingFileHandler::class,
                        'construct' => [
                            'filename' => \dirname(__DIR__) . '/logs/log.log',
                        ],
                        'formatter' => [
                            'class'     => \Monolog\Formatter\LineFormatter::class,
                            'construct' => [
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    // 主服务器配置
    'mainServer'        => [
        'namespace'    => 'Imi\Swoole\Test\WebSocketServerWithAmqpServerUtil\MainServer',
        'type'         => Imi\Swoole\Server\Type::WEBSOCKET,
        'host'         => env('SERVER_HOST', '127.0.0.1'),
        'port'         => 13009,
        'mode'         => \SWOOLE_BASE,
        'configs'      => [
            'worker_num'    => 2,
            'max_wait_time' => 30,
        ],
    ],

    // 子服务器（端口监听）配置
    'subServers'        => [
    ],

    'imi'               => [
        'beans' => [
            'ServerUtil' => 'AmqpServerUtil',
        ],
    ],

    // 连接池配置
    'pools'             => [
        'rabbit'   => [
            'pool'        => [
                'class'        => \Imi\AMQP\Pool\AMQPCoroutinePool::class,
                'config'       => [
                    'maxResources'    => 10,
                    'minResources'    => 0,
                ],
            ],
            'resource'    => [
                'host'      => AMQP_SERVER_HOST,
                'port'      => 5672,
                'user'      => 'guest',
                'password'  => 'guest',
            ],
        ],
    ],

    'connectionCenter' => [
        'redis'            => [
            'manager' => \Imi\ConnectionCenter\Handler\Pool\PoolConnectionManager::class,
            'pool'    => [
                'maxResources'    => 10,
                'minResources'    => 0,
            ],
            'config'  => [
                'driver'    => \Imi\Redis\Connector\RedisConnectionDriver::class,
                'resources' => [
                    [
                        'host'      => env('REDIS_SERVER_HOST', '127.0.0.1'),
                        'port'      => env('REDIS_SERVER_PORT', 6379),
                        'password'  => env('REDIS_SERVER_PASSWORD'),

                        'client' => 'phpredis',
                        'mode'   => \Imi\Redis\Enum\RedisMode::Standalone,
                    ],
                ],
            ],
        ],
    ],

    // redis 配置
    'redis'             => [
        // 默认连接池名
        'defaultPool'   => 'redis',
    ],

    // 内存表配置
    'memoryTable'       => [
        'ConnectionContext'    => [
            'class'      => \Imi\Swoole\Server\ConnectionContext\StoreHandler\MemoryTable\ConnectionContextOption::class,
            'lockId'     => 'atomic',
            'dataLength' => 2048,
        ],
    ],

    // atmoic 配置
    'atomics'           => [
        'atomic1'   => 1,
    ],

    // 锁配置
    'lock'              => [
        'default' => 'atomic',
        'list'    => [
            'atomic'                     => [
                'class'     => 'AtomicLock',
                'options'   => [
                    'atomicName'    => 'atomic1',
                ],
            ],
            'redisConnectionContextLock' => [
                'class'     => 'RedisLock',
                'options'   => [
                    'poolName'  => 'redis',
                ],
            ],
        ],
    ],
];
