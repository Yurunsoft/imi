<?php

declare(strict_types=1);

use function Imi\env;

return [
    // 组件命名空间
    'components'                 => [
        'Database' => 'Imi\Db',
    ],

    'beans'             => [],

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

    'connectionCenter' => [
        // 主数据库
        'maindb' => [
            'manager' => \Imi\ConnectionCenter\Handler\RequestContextSingleton\RequestContextSingletonConnectionManager::class,
            'config'  => [
                'driver'    => \Imi\Db\ConnectionCenter\DatabaseDriver::class,
                'dbDriver'  => \Imi\Db\Mysql\Drivers\PDOMysql\Driver::class,
                'resources' => [
                    [
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
            ],
        ],
        // 从数据库
        'maindb.slave' => [
            'manager' => \Imi\ConnectionCenter\Handler\RequestContextSingleton\RequestContextSingletonConnectionManager::class,
            'config'  => [
                'driver'    => \Imi\Db\ConnectionCenter\DatabaseDriver::class,
                'dbDriver'  => \Imi\Db\Mysql\Drivers\PDOMysql\Driver::class,
                'resources' => [
                    [
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
            ],
        ],
        'mysqli' => [
            'manager' => \Imi\ConnectionCenter\Handler\RequestContextSingleton\RequestContextSingletonConnectionManager::class,
            'config'  => [
                'driver'    => \Imi\Db\ConnectionCenter\DatabaseDriver::class,
                'dbDriver'  => \Imi\Db\Mysql\Drivers\Mysqli\Driver::class,
                'resources' => [
                    [
                        'host'        => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                        'port'        => env('MYSQL_SERVER_PORT', 3306),
                        'username'    => env('MYSQL_SERVER_USERNAME', 'root'),
                        'password'    => env('MYSQL_SERVER_PASSWORD', 'root'),
                        'database'    => 'db_imi_test',
                        'charset'     => 'utf8mb4',
                    ],
                ],
            ],
        ],
        'dbPrefix' => [
            'manager' => \Imi\ConnectionCenter\Handler\RequestContextSingleton\RequestContextSingletonConnectionManager::class,
            'config'  => [
                'driver'    => \Imi\Db\ConnectionCenter\DatabaseDriver::class,
                'dbDriver'  => \Imi\Db\Mysql\Drivers\PDOMysql\Driver::class,
                'resources' => [
                    [
                        'host'        => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                        'port'        => env('MYSQL_SERVER_PORT', 3306),
                        'username'    => env('MYSQL_SERVER_USERNAME', 'root'),
                        'password'    => env('MYSQL_SERVER_PASSWORD', 'root'),
                        'database'    => 'db_imi_test',
                        'charset'     => 'utf8mb4',
                        'prefix'      => 'tb_',
                    ],
                ],
            ],
        ],
    ],
    // db 配置
    'db'                => [
        // 默认连接池名
        'defaultPool' => 'maindb',
    ],
];
