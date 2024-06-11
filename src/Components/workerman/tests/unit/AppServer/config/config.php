<?php

declare(strict_types=1);

use Imi\Server\WebSocket\Enum\NonControlFrameType;

use function Imi\env;

return [
    // 配置文件
    'configs'         => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 扫描目录
    'beanScan'        => [
        'Imi\Workerman\Test\AppServer\Listener',
        'Imi\Workerman\Test\AppServer\Cron',
    ],

    // 组件命名空间
    'components'      => [
        'Workerman'        => 'Imi\Workerman',
        'Macro'            => 'Imi\Macro',
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

    // 统一的服务器配置
    'server'          => [
        // 启动服务时，检查连接池连接或非连接池连接是否可用。如不可用直接退出程序。
        'checkPoolResource' => true,
    ],

    // Workerman 服务器配置
    'workermanServer' => [
        'http'      => [
            'namespace' => 'Imi\Workerman\Test\AppServer\ApiServer',
            'type'      => Imi\Workerman\Server\Type::HTTP,
            'host'      => env('SERVER_HOST', '127.0.0.1'),
            'port'      => 13000,
            'configs'   => [
            ],
            'appUri' => TEST_APP_URI_CONFIG,
        ],
        'websocket' => [
            'namespace'           => 'Imi\Workerman\Test\AppServer\WebSocketServer',
            'type'                => Imi\Workerman\Server\Type::WEBSOCKET,
            'host'                => env('SERVER_HOST', '127.0.0.1'),
            'port'                => 13002,
            'shareWorker'         => 'http',
            'nonControlFrameType' => NonControlFrameType::Binary,
            'configs'             => [
            ],
        ],
        'tcp'       => [
            'namespace'     => 'Imi\Workerman\Test\AppServer\TcpServer',
            'type'          => Imi\Workerman\Server\Type::TCP,
            'host'          => env('SERVER_HOST', '127.0.0.1'),
            'port'          => 13003,
            'shareWorker'   => 'http',
            'configs'       => [
                'protocol' => \Workerman\Protocols\Text::class,
            ],
            // 数据处理器
            'dataParser'    => \Imi\Workerman\Test\AppServer\TcpServer\Parser\JsonObjectParser::class,
        ],
        'udp'       => [
            'namespace'     => 'Imi\Workerman\Test\AppServer\UdpServer',
            'type'          => Imi\Workerman\Server\Type::UDP,
            'host'          => env('SERVER_HOST', '127.0.0.1'),
            'port'          => 13004,
            'shareWorker'   => 'http',
            'configs'       => [
            ],
            // 数据处理器
            'dataParser'    => Imi\Server\DataParser\JsonObjectParser::class,
        ],
    ],

    'workerman'       => [
        'worker' => [
            'stopTimeout' => 30,
        ],
    ],

    // 数据库配置
    'db'              => [
        // 默认连接池名
        'defaultPool'    => 'maindb',
        'connections'    => [
            'maindb'         => [
                'host'              => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                'port'              => env('MYSQL_SERVER_PORT', 3306),
                'username'          => env('MYSQL_SERVER_USERNAME', 'root'),
                'password'          => env('MYSQL_SERVER_PASSWORD', 'root'),
                'database'          => 'mysql',
                'charset'           => 'utf8mb4',
                'heartbeatInterval' => 30,
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
            'ServerUtil' => \Imi\Workerman\Server\Util\LocalServerUtil::class,
        ],
    ],
];
