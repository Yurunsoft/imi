<?php

declare(strict_types=1);

use Imi\Util\Imi;

use function Imi\env;

\defined('AMQP_SERVER_HOST') || \define('AMQP_SERVER_HOST', env('AMQP_SERVER_HOST', '127.0.0.1'));

return [
    // 配置文件
    'configs'                    => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 扫描目录
    'beanScan'                   => [
        'AMQPApp\Listener',
        'AMQPApp\Task',
        'AMQPApp\Consumer',
        'AMQPApp\AMQP',
        'AMQPApp\Process',
    ],

    // 组件命名空间
    'components'                 => [
        'Swoole'    => 'Imi\Swoole',
        'Workerman' => 'Imi\Workerman',
        'AMQP'      => 'Imi\AMQP',
    ],

    'overrideDefaultIgnorePaths' => true,

    // 主服务器配置
    'mainServer'                 => [
        'namespace'    => 'AMQPApp\ApiServer',
        'type'         => \Imi\Swoole\Server\Type::HTTP,
        'host'         => '127.0.0.1',
        'port'         => 8080,
        'configs'      => [
            'worker_num'        => 1,
            'max_wait_time'     => 30,
        ],
    ],

    // Workerman 服务器配置
    'workermanServer'            => [
        'http' => [
            'namespace'    => 'AMQPApp\ApiServer',
            'type'         => \Imi\Workerman\Server\Type::HTTP,
            'host'         => '127.0.0.1',
            'port'         => 8080,
            'configs'      => [
            ],
        ],
    ],

    'workerman'       => [
        'worker' => [
            'stopTimeout' => 30,
        ],
    ],

    // 子服务器（端口监听）配置
    'subServers'                 => [
    ],

    // 连接池配置
    'pools'                      => Imi::checkAppType('swoole') ? [
        'rabbit'    => [
            'pool'        => [
                'class'        => \Imi\AMQP\Pool\AMQPCoroutinePool::class,
                'config'       => [
                    'maxResources'    => 10,
                    'minResources'    => 0,
                ],
            ],
            'resource'    => [
                'host'            => AMQP_SERVER_HOST,
                'port'            => 5672,
                'user'            => 'guest',
                'password'        => 'guest',
                'keepalive'       => false, // 截止 Swoole 4.8 还有兼容问题，所以必须设为 false，不影响使用
            ],
        ],
    ] : [],

    // 连接中心配置
    'connectionCenter' => [
        'redis'            => [
            'manager' =>  Imi::checkAppType('swoole') ? \Imi\ConnectionCenter\Handler\Pool\PoolConnectionManager::class :  \Imi\ConnectionCenter\Handler\Singleton\SingletonConnectionManager::class,
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
    'redis'                      => [
        // 数默认连接池名
        'defaultPool' => 'redis',
    ],
    'amqp'                       => [
        'connections' => [
            'rabbit'    => [
                'host'      => AMQP_SERVER_HOST,
                'port'      => 5672,
                'user'      => 'guest',
                'password'  => 'guest',
            ],
        ],
    ],
    // 日志配置
    'logger'                     => [
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
