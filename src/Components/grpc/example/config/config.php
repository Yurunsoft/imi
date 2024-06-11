<?php

declare(strict_types=1);

use Imi\Grpc\Client\GrpcClient;

use function Imi\env;

return [
    // 配置文件
    'configs'           => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 扫描目录
    'beanScan'          => [
        'GrpcApp\Listener',
    ],

    // 组件命名空间
    'components'        => [
        'Swoole' => 'Imi\Swoole',
        'Grpc'   => 'Imi\Grpc',
    ],

    // 忽略目录
    'ignorePaths'       => [
        \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'grpc',
    ],

    // 主服务器配置
    'mainServer'        => [
        'namespace' => 'GrpcApp\GrpcServer',
        'type'      => 'GrpcServer',
        'host'      => '127.0.0.1',
        'port'      => 8080,
        'configs'   => [
            'worker_num'        => 1,
            'max_wait_time'     => 30,
        ],
    ],

    // 子服务器（端口监听）配置
    'subServers'        => [
        'SubServerName'   => [
            'namespace'    => 'GrpcApp\ApiServer',
            'type'         => Imi\Swoole\Server\Type::HTTP,
            'host'         => '127.0.0.1',
            'port'         => 8081,
        ],
    ],

    // 连接池配置
    'pools'             => [
        'grpc'     => [
            'pool'        => [
                'class'        => \Imi\Rpc\Client\Pool\RpcClientCoroutinePool::class,
                'config'       => [
                    'maxResources'  => 100,
                    'minResources'  => 1,
                ],
            ],
            'resource'    => [
                'url'           => 'http://127.0.0.1:8080/{package}.{service}/{name}',
                'clientClass'   => GrpcClient::class,
                'timeout'       => 30,
            ],
        ],
    ],

    // 连接中心配置
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

    // 数据库配置
    'db'                => [
        // 数默认连接池名
        'defaultPool'    => 'maindb',
    ],

    // redis 配置
    'redis'             => [
        // 数默认连接池名
        'defaultPool'   => 'redis',
    ],

    // 内存表配置
    'memoryTable'       => [
        'ConnectionContext'    => [
            'class'  => \Imi\Swoole\Server\ConnectionContext\StoreHandler\MemoryTable\ConnectionContextOption::class,
            'lockId' => 'redisConnectionContextLock',
            'size'   => 65536,
        ],
    ],

    // 锁
    'lock'              => [
        'list'  => [
            'redisConnectionContextLock' => [
                'class'     => 'RedisLock',
                'options'   => [
                    'poolName'  => 'redis',
                ],
            ],
        ],
    ],

    'rpc'               => [
        'defaultPool'   => 'grpc',
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
