<?php

declare(strict_types=1);

use function Imi\env;

return [
    // 配置文件
    'configs'         => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 扫描目录
    'beanScan'        => [
        'Imi\Workerman\Test\ChannelServerUtilServer\Listener',
        'Imi\Workerman\Test\ChannelServerUtilServer\Cron',
    ],

    // 组件命名空间
    'components'      => [
        'Workerman' => 'Imi\Workerman',
        'Macro'     => 'Imi\Macro',
    ],

    // 日志配置
    'logger'          => [
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

    // Workerman 服务器配置
    'workermanServer' => [
        'channel'   => [
            'namespace'   => '',
            'type'        => Imi\Workerman\Server\Type::CHANNEL,
            'host'        => env('SERVER_HOST', '127.0.0.1'),
            'port'        => 13005,
            'configs'     => [
            ],
        ],
        'http'      => [
            'namespace' => 'Imi\Workerman\Test\ChannelServerUtilServer\ApiServer',
            'type'      => Imi\Workerman\Server\Type::HTTP,
            'host'      => env('SERVER_HOST', '127.0.0.1'),
            'port'      => 13006,
            'configs'   => [
                'count' => 'Linux' === \PHP_OS ? 2 : 1,
            ],
        ],
        'websocket' => [
            'namespace'   => 'Imi\Workerman\Test\ChannelServerUtilServer\WebSocketServer',
            'type'        => Imi\Workerman\Server\Type::WEBSOCKET,
            'host'        => env('SERVER_HOST', '127.0.0.1'),
            'port'        => 13007,
            'shareWorker' => 'http',
        ],
    ],

    'workerman'       => [
        'worker' => [
            'stopTimeout' => 30,
        ],
        // 多进程通讯组件配置
        'channel' => [
            'host' => env('SERVER_HOST', '127.0.0.1'),
            'port' => 13005,
        ],
    ],

    // 数据库配置
    'db'              => [
        // 默认连接池名
        'defaultPool'    => 'maindb',
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
    'redis'           => [
        // 默认连接池名
        'defaultPool'   => 'redis',
    ],

    // 锁
    'lock'            => [
        'default' => 'redisConnectionContextLock',
        'list'    => [
            'redisConnectionContextLock' => [
                'class'     => 'RedisLock',
                'options'   => [
                    'poolName'  => 'redis',
                ],
            ],
        ],
    ],

    'imi'             => [
        'beans' => [
            'ServerUtil' => \Imi\Workerman\Server\Util\ChannelServerUtil::class,
        ],
    ],
];
