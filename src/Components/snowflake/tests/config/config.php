<?php

declare(strict_types=1);

use function Imi\env;

return [
    'configs'           => [
    ],
    'components'        => [
        // 引入本组件
        'snowflake'    => 'Imi\Snowflake',
    ],
    'ignoreNamespace'   => [
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
                    ],
                ],
            ],
        ],
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
    ],
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
    ],
    // redis 配置
    'redis'             => [
        // 数默认连接池名
        'defaultPool'   => 'redis_test',
    ],
    'beans'             => [
        'JWT'       => [
            'list'  => [
                'a' => [
                    'audience'      => 'audience_a',
                    'subject'       => 'subject_a',
                    'expires'       => 86400,
                    'issuer'        => 'issuer_a',
                    'headers'       => [
                        'a' => '1',
                        'b' => '2',
                    ],
                    'tokenHandler'  => static fn () => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImEiOiIxIiwiYiI6IjIifQ.eyJhdWQiOiJhdWRpZW5jZV9hIiwic3ViIjoic3ViamVjdF9hIiwiZXhwIjoxODkzMjkxNTE1LCJpc3MiOiJpc3N1ZXJfYSIsIm5iZiI6MCwianRpIjoiIiwiaWF0IjoxNTc3OTMxNTE1LCJkYXRhIjp7Im1lbWJlcklkIjoxOTI2MDgxN319.-tXlyj1BcVD8GJIE2nQdTPULVpZFD0h5BIQdx_X943E',
                    'privateKey'    => '123456',
                    'publicKey'     => '123456',
                ],
            ],
        ],
        'Snowflake' => [
            'list'  => [
                'testBasic'   => [
                ],
                'test1'       => [
                    'datacenterId'   => -1,
                    'workerId'       => -1,
                ],
                'test2'       => [
                    'datacenterId'   => 33,
                    'workerId'       => -1,
                ],
                'test3'       => [
                    'datacenterId'   => 1,
                    'workerId'       => 2,
                ],
                'test4'       => [
                    'datacenterId'   => 999,
                    'workerId'       => 20,
                ],
                'testBatch'   => [
                    'datacenterId'   => 999,
                    'workerId'       => 20,
                ],
                'testParseId' => [
                    'datacenterId'   => 999,
                    'workerId'       => 20,
                ],
            ],
        ],
    ],
];
