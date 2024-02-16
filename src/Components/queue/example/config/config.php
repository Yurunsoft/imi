<?php

declare(strict_types=1);

use Imi\Util\Imi;

use function Imi\env;

return [
    // 配置文件
    'configs'         => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 扫描目录
    // 'beanScan'    => [
    //     'QueueApp\Consumer',
    //     'QueueApp\Listener',
    // ],

    // 组件命名空间
    'components'      => [
        'Queue'     => 'Imi\Queue',
        'Swoole'    => 'Imi\Swoole',
        'Workerman' => 'Imi\Workerman',
    ],

    // 主服务器配置
    'mainServer'      => [
        'namespace'     => 'QueueApp\HttpServer',
        'type'          => \Imi\Swoole\Server\Type::HTTP,
        'host'          => '127.0.0.1',
        'port'          => 8080,
        'configs'       => [
            'worker_num'        => 1,
            'max_wait_time'     => 30,
        ],
    ],

    // Workerman 服务器配置
    'workermanServer' => [
        'http' => [
            'namespace' => 'QueueApp\HttpServer',
            'type'      => \Imi\Workerman\Server\Type::HTTP,
            'host'      => '127.0.0.1',
            'port'      => 8080,
            'configs'   => [
            ],
        ],
    ],

    'workerman'       => [
        'worker' => [
            'stopTimeout' => 30,
        ],
    ],

    // 连接池配置
    'pools'           => [],

    // 连接中心配置
    'connectionCenter' => Imi::checkAppType('swoole') ? [
        'redis'            => [
            'manager' => \Imi\ConnectionCenter\Handler\Pool\PoolConnectionManager::class,
            'pool' => [
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
                        'database' => 0,
                    ],
                ],
            ],
        ],
    ] : [],

    // 数据库配置
    'db'              => [
        // 数默认连接池名
        'defaultPool'    => 'maindb',
    ],

    // redis 配置
    'redis'           => [
        // 数默认连接池名
        'defaultPool'   => 'redis',
    ],

    // 锁
    'lock'            => [
        'list'  => [
            'redisConnectionContextLock' => [
                'class'     => 'RedisLock',
                'options'   => [
                    'poolName'  => 'redis',
                ],
            ],
        ],
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
                            'filename' => \dirname(__DIR__) . '/.runtime/logs/log.log',
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
];
