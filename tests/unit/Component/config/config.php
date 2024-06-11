<?php

declare(strict_types=1);

use function Imi\env;

return [
    'configs'           => [
    ],
    // bean扫描目录
    'beanScan'          => [
        'Imi\Test\Component\Tests',
        'Imi\Test\Component\Aop',
        'Imi\Test\Component\Inject',
        'Imi\Test\Component\Event',
        'Imi\Test\Component\Enum',
        'Imi\Test\Component\Redis',
        'Imi\Test\Component\Db',
        'Imi\Test\Component\Cache',
        'Imi\Test\Component\Lock',
        'Imi\Test\Component\Model',
        'Imi\Test\Component\Validate',
        'Imi\Test\Component\Inherit',
        'Imi\Test\Component\Util\Imi',
        'Imi\Test\Component\Facade',
        'Imi\Test\Component\Annotation',
        'Imi\Test\Component\Partial',
        'Imi\Test\Component\Tool',
        'Imi\Test\Component\RequestContextProxy',
        'Imi\Test\Component\Bean',
        'Imi\Test\Component\Async',
    ],
    'ignoreNamespace'   => [
        'Imi\Test\Component\Annotation\A\*',
        'Imi\Test\Component\Annotation\B\TestB',
    ],
    'ignorePaths'       => [
        \dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'test.php',
    ],

    // 组件命名空间
    'components'        => [
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
        'TestPropertyClass' => [
            'b' => 'bbb',
        ],
        'ErrorLog'          => [
            'catchLevel'         => \E_ALL,
            'exceptionLevel'     => \E_ALL,
            'errorEventHandlers' => [
                \Imi\Test\Component\ErrorEventHandler::class,
            ],
        ],
        'DbQueryLog'        => [
            'enable' => true,
        ],
        'EnumBean1' => [
            'enum1' => 'A',
            'enum2' => 'imi',
            'enum3' => 'A',
        ],
        'EnumBean2' => [
            'enum1' => 'B',
            'enum2' => 'hello',
            'enum3' => 'imi',
        ],
    ],
    'imi-framework'     => 'very six',

    // 连接池配置
    'pools'             => [
    ],
    // db 配置
    'db'                => [
        // 默认连接池名
        'defaultPool' => 'maindb',
        'connections' => [
            'tradition' => [
                'dbClass'  => 'PDOMysqlDriver',
                'host'     => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                'port'     => env('MYSQL_SERVER_PORT', 3306),
                'username' => env('MYSQL_SERVER_USERNAME', 'root'),
                'password' => env('MYSQL_SERVER_PASSWORD', 'root'),
                'database' => 'db_imi_test',
                'charset'  => 'utf8mb4',
            ],
        ],
        'statement'   => [
            'maxCacheCount' => 32,
        ],
    ],
    // redis 配置
    'redis'             => [
        // 默认连接池名
        'defaultPool'   => 'redis_test',
    ],

    // 连接中心配置
    'connectionCenter' => [
        'redis_test'            => [
            'manager' => \Imi\ConnectionCenter\Handler\Singleton\SingletonConnectionManager::class,
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
        'predis_test'            => [
            'manager' => \Imi\ConnectionCenter\Handler\Singleton\SingletonConnectionManager::class,
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

                        'client' => 'predis',
                        'mode'   => \Imi\Redis\Enum\RedisMode::Standalone,
                    ],
                ],
            ],
        ],
    ],

    // 缓存配置
    'cache'             => [
        'default'   => 'file1',
    ],
    // 缓存
    'caches'            => [
        'file1'          => [
            'handlerClass'  => \Imi\Cache\Handler\File::class,
            'option'        => [
                'savePath'              => sys_get_temp_dir() . '/imi-cache/',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
        'file2'          => [
            'handlerClass'  => \Imi\Cache\Handler\File::class,
            'option'        => [
                'savePath'                => sys_get_temp_dir() . '/imi-cache/',
                // 保存文件名处理回调，一般可以不写
                'saveFileNameCallback'    => static fn (string $savePath, string $key) => \Imi\Util\File::path($savePath, sha1($key)),
                'formatHandlerClass'      => \Imi\Util\Format\Json::class,
            ],
        ],
        'file3'          => [
            'handlerClass'  => \Imi\Cache\Handler\File::class,
            'option'        => [
                'savePath'              => sys_get_temp_dir() . '/imi-cache/',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
                'keyAsFileName'         => true,
            ],
        ],
        'redis'          => [
            'handlerClass'  => \Imi\Cache\Handler\Redis::class,
            'option'        => [
                'poolName'              => 'redis_test',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
        'predis'          => [
            'handlerClass'  => \Imi\Cache\Handler\Redis::class,
            'option'        => [
                'poolName'              => 'predis_test',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
        'redisHash'      => [
            'handlerClass'  => \Imi\Cache\Handler\RedisHash::class,
            'option'        => [
                'poolName'              => 'redis_test',
                'separator'             => '->',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
        'predisHash'     => [
            'handlerClass'  => \Imi\Cache\Handler\RedisHash::class,
            'option'        => [
                'poolName'              => 'predis_test',
                'separator'             => '->',
                'formatHandlerClass'    => \Imi\Util\Format\Json::class,
            ],
        ],
        'apcu'           => [
            'handlerClass'  => \Imi\Cache\Handler\Apcu::class,
            'option'        => [
            ],
        ],
        'requestContext' => [
            'handlerClass'  => \Imi\Cache\Handler\RequestContext::class,
            'option'        => [
            ],
        ],
        'memory'         => [
            'handlerClass'  => \Imi\Cache\Handler\Memory::class,
            'option'        => [
            ],
        ],
    ],
    // atmoic 配置
    'atomics'           => [
        'test',
    ],
    // 锁
    'lock'              => [
        'list'  => [
            'redis' => [
                'class'     => 'RedisLock',
                'options'   => [
                    'poolName'  => 'redis_test',
                ],
            ],
            'predis' => [
                'class'     => 'RedisLock',
                'options'   => [
                    'poolName'  => 'predis_test',
                ],
            ],
        ],
    ],
    'yurun2'            => env('yurun'),
];
