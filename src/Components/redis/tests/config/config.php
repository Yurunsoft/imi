<?php

declare(strict_types=1);

use function Imi\env;

return [
    // 组件命名空间
    'components'        => [
        'model' => 'Imi\Redis',
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

    'beans'             => [
        'ErrorLog'          => [
            'catchLevel'         => \E_ALL,
            'exceptionLevel'     => \E_ALL,
            'errorEventHandlers' => [
                \Imi\Test\Component\ErrorEventHandler::class,
            ],
        ],
    ],

    'connectionCenter' => [
        'test_phpredis_standalone'            => [
            'manager' => \Imi\ConnectionCenter\Handler\Singleton\SingletonConnectionManager::class,
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
                    ],
                ],
            ],
        ],
        'test_phpredis_cluster'            => [
            'manager' => \Imi\ConnectionCenter\Handler\Singleton\SingletonConnectionManager::class,
            'pool' => [
                'maxResources'    => 10,
                'minResources'    => 0,
            ],
            'config'  => [
                'driver'    => \Imi\Redis\Connector\RedisConnectionDriver::class,
                'resources' => [
                    [
                        'password'  => env('REDIS_SERVER_CLUSTER_PASSWORD'),

                        'client' => 'phpredis',
                        'mode'   => \Imi\Redis\Enum\RedisMode::Cluster,

                        'seeds' => [
                            '192.168.32.2:6379',
                            '192.168.32.3:6379',
                            '192.168.32.4:6379',
                            '192.168.32.5:6379',
                            '192.168.32.6:6379',
                            '192.168.32.7:6379',
                        ],
                    ],
                ],
            ],
        ],
        'test_predis_standalone'            => [
            'manager' => \Imi\ConnectionCenter\Handler\Singleton\SingletonConnectionManager::class,
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

                        'client' => 'predis',
                        'mode'   => \Imi\Redis\Enum\RedisMode::Standalone,
                    ],
                ],
            ],
        ],
        'test_predis_cluster'            => [
            'manager' => \Imi\ConnectionCenter\Handler\Singleton\SingletonConnectionManager::class,
            'pool' => [
                'maxResources'    => 10,
                'minResources'    => 0,
            ],
            'config'  => [
                'driver'    => \Imi\Redis\Connector\RedisConnectionDriver::class,
                'resources' => [
                    [
                        'password'  => env('REDIS_SERVER_CLUSTER_PASSWORD'),

                        'client' => 'predis',
                        'mode'   => \Imi\Redis\Enum\RedisMode::Cluster,

                        'seeds' => [
                            '192.168.32.2:6379',
                            '192.168.32.3:6379',
                            '192.168.32.4:6379',
                            '192.168.32.5:6379',
                            '192.168.32.6:6379',
                            '192.168.32.7:6379',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // redis 配置
    'redis'             => [
        // 默认连接池名
        'defaultPool'   => 'test_phpredis_standalone',
        'quickFromRequestContext' => true,
    ],
    // 缓存配置
    'cache'             => [
        'default'   => 'redis',
    ],
    // 缓存
    'caches'            => [
        'redis'          => [
            'handlerClass'  => \Imi\Cache\Handler\Redis::class,
            'option'        => [
                'poolName'              => 'test_phpredis_standalone',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
        'redisHash'      => [
            'handlerClass'  => \Imi\Cache\Handler\RedisHash::class,
            'option'        => [
                'poolName'              => 'test_phpredis_standalone',
                'separator'             => '->',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
    ],
];
