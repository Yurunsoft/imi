<?php

declare(strict_types=1);

use function Imi\env;

return [
    'configs'       => [
    ],
    // bean扫描目录
    'beanScan'      => [
        'Imi\Swoole\Test\Component\Cache',
        'Imi\Swoole\Test\Component\Db',
        'Imi\Swoole\Test\Component\Lock',
        'Imi\Swoole\Test\Component\Tests',
        'Imi\Swoole\Test\Component\Async',
        'Imi\Swoole\Test\Component\Pool',
    ],
    'ignorePaths' => [
        \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'test.php',
    ],

    // 组件命名空间
    'components'    => [
        'Swoole' => 'Imi\Swoole',
        'Macro'  => 'Imi\Macro',
    ],

    // 日志配置
    'logger'        => [
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

    'beans'         => [
        'ErrorLog'          => [
            // 'level' =>  ,
        ],
        'DbQueryLog'        => [
            'enable' => true,
        ],
    ],

    // 连接池配置
    'pools'         => [
        // 主数据库
        'maindb'                => [
            'pool'        => [
                // 协程池类名
                'class'         => \Imi\Swoole\Db\Pool\CoroutineDbPool::class,
                // 连接池配置
                'config'        => [
                    'maxResources'              => 10,
                    'minResources'              => 1,
                    'checkStateWhenGetResource' => false,
                ],
            ],
            // 连接池资源配置
            'resource'    => [
                'host'        => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                'port'        => env('MYSQL_SERVER_PORT', 3306),
                'username'    => env('MYSQL_SERVER_USERNAME', 'root'),
                'password'    => env('MYSQL_SERVER_PASSWORD', 'root'),
                'database'    => 'db_imi_test',
                'charset'     => 'utf8mb4',
                'initSqls'    => [
                    'SET @__pool_name="maindb"',
                ],
            ],
        ],
        // 主数据库
        'maindb.slave'          => [
            'pool'        => [
                // 协程池类名
                'class'         => \Imi\Swoole\Db\Pool\CoroutineDbPool::class,
                // 连接池配置
                'config'        => [
                    'maxResources'              => 10,
                    'minResources'              => 1,
                    'checkStateWhenGetResource' => false,
                ],
            ],
            // 连接池资源配置
            'resource'    => [
                'host'        => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                'port'        => env('MYSQL_SERVER_PORT', 3306),
                'username'    => env('MYSQL_SERVER_USERNAME', 'root'),
                'password'    => env('MYSQL_SERVER_PASSWORD', 'root'),
                'database'    => 'db_imi_test',
                'charset'     => 'utf8mb4',
                'initSqls'    => [
                    'SET @__pool_name="maindb.slave"',
                ],
            ],
        ],
        // 主数据库
        'swooleMysql'           => [
            'pool'        => [
                // 协程池类名
                'class'         => \Imi\Swoole\Db\Pool\CoroutineDbPool::class,
                // 连接池配置
                'config'        => [
                    'maxResources'              => 10,
                    'minResources'              => 1,
                    'checkStateWhenGetResource' => false,
                ],
            ],
            // 连接池资源配置
            'resource'    => [
                'host'        => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                'port'        => env('MYSQL_SERVER_PORT', 3306),
                'username'    => env('MYSQL_SERVER_USERNAME', 'root'),
                'password'    => env('MYSQL_SERVER_PASSWORD', 'root'),
                'database'    => 'db_imi_test',
                'charset'     => 'utf8mb4',
                'dbClass'     => \Imi\Swoole\Db\Driver\Swoole\Driver::class,
            ],
        ],
        // mysqli
        'mysqli'                => [
            'pool'        => [
                // 协程池类名
                'class'         => \Imi\Swoole\Db\Pool\CoroutineDbPool::class,
                // 连接池配置
                'config'        => [
                    'maxResources'              => 10,
                    'minResources'              => 1,
                    'checkStateWhenGetResource' => false,
                ],
            ],
            // 连接池资源配置
            'resource'    => [
                'host'        => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                'port'        => env('MYSQL_SERVER_PORT', 3306),
                'username'    => env('MYSQL_SERVER_USERNAME', 'root'),
                'password'    => env('MYSQL_SERVER_PASSWORD', 'root'),
                'database'    => 'db_imi_test',
                'charset'     => 'utf8mb4',
                'dbClass'     => 'MysqliDriver',
            ],
        ],
        'redis_test'            => [
            'pool'        => [
                'class'        => \Imi\Swoole\Redis\Pool\CoroutineRedisPool::class,
                'config'       => [
                    'maxResources'    => 10,
                    'minResources'    => 1,
                ],
            ],
            'resource'    => [
                'host'      => env('REDIS_SERVER_HOST', '127.0.0.1'),
                'port'      => env('REDIS_SERVER_PORT', 6379),
                'password'  => env('REDIS_SERVER_PASSWORD'),
            ],
        ],
        'redis_cache'           => [
            'pool'        => [
                'class'        => \Imi\Swoole\Redis\Pool\CoroutineRedisPool::class,
                'config'       => [
                    'maxResources'    => 10,
                    'minResources'    => 1,
                ],
            ],
            'resource'    => [
                'host'        => env('REDIS_SERVER_HOST', '127.0.0.1'),
                'port'        => env('REDIS_SERVER_PORT', 6379),
                'password'    => env('REDIS_SERVER_PASSWORD'),
                'serialize'   => false,
                'db'          => 1,
            ],
        ],
        'redis_manager_test'    => [
            'pool'        => [
                'class'        => \Imi\Swoole\Redis\Pool\CoroutineRedisPool::class,
                'config'       => [
                    'maxResources'    => 10,
                    'minResources'    => 1,
                ],
            ],
            'resource'    => [
                'host'        => env('REDIS_SERVER_HOST', '127.0.0.1'),
                'port'        => env('REDIS_SERVER_PORT', 6379),
                'password'    => env('REDIS_SERVER_PASSWORD'),
                'serialize'   => false,
                'db'          => 1,
            ],
        ],
    ],
    // db 配置
    'db'            => [
        // 默认连接池名
        'defaultPool'   => 'maindb',
    ],
    // redis 配置
    'redis'         => [
        // 默认连接池名
        'defaultPool'   => 'redis_test',
    ],
    // 缓存配置
    'cache'         => [
        'default'   => 'file1',
    ],
    // 缓存
    'caches'        => [
        'file1'     => [
            'handlerClass'  => \Imi\Cache\Handler\File::class,
            'option'        => [
                'savePath'              => sys_get_temp_dir() . '/imi-cache/',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
        'file2'     => [
            'handlerClass'  => \Imi\Cache\Handler\File::class,
            'option'        => [
                'savePath'                => sys_get_temp_dir() . '/imi-cache/',
                // 保存文件名处理回调，一般可以不写
                'saveFileNameCallback'    => static fn (string $savePath, string $key) => \Imi\Util\File::path($savePath, sha1($key)),
                'formatHandlerClass'      => \Imi\Util\Format\Json::class,
            ],
        ],
        'redis'     => [
            'handlerClass'  => \Imi\Cache\Handler\Redis::class,
            'option'        => [
                'poolName'              => 'redis_cache',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
        'redisHash' => [
            'handlerClass'  => \Imi\Cache\Handler\RedisHash::class,
            'option'        => [
                'poolName'              => 'redis_cache',
                'separator'             => '->',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
    ],
    // atmoic 配置
    'atomics'       => [
        'atomicLock'   => 1,
        'test',
    ],
    // 锁
    'lock'          => [
        'list'  => [
            'atomic' => [
                'class'     => 'AtomicLock',
                'options'   => [
                    'atomicName'    => 'atomicLock',
                ],
            ],
        ],
    ],
    'tools'         => [
    ],
];
