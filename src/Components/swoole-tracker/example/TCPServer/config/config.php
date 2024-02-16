<?php

declare(strict_types=1);

use function Imi\env;

return [
    // 配置文件
    'configs'           => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 扫描目录
    'beanScan'          => [
        'Imi\SwooleTracker\Example\TCPServer\Listener',
        'Imi\SwooleTracker\Example\TCPServer\Task',
    ],

    // 组件命名空间
    'components'        => [
        'SwooleTracker'       => 'Imi\SwooleTracker',
    ],

    // 主服务器配置
    'mainServer'        => [
        'namespace'         => 'Imi\SwooleTracker\Example\TCPServer\TCPServer',
        'type'              => Imi\Swoole\Server\Type::TCP_SERVER,
        'host'              => '127.0.0.1',
        'port'              => 8082,
        'configs'           => [
            // 'worker_num'        =>  8,
            // 'task_worker_num'   =>  16,

            // 分包方式

            // 分包方式1-EOF自动分包
            'open_eof_split'    => true, // 打开EOF_SPLIT检测
            'package_eof'       => "\r\n", // 设置EOF

            // 分包方式2-固定包头
            // 'open_eof_split'        => false,
            // 'open_length_check'     => true,
            // 'package_length_type'   => 'N',
            // 'package_length_offset' => 0,       //第N个字节是包长度的值
            // 'package_body_offset'   => 4,       //第几个字节开始计算长度
            // 'package_max_length'    => 1024 * 1024,  //协议最大长度

            'max_wait_time' => 30,
        ],
        // EOF自动分包数据处理器
        'dataParser'        => \Imi\SwooleTracker\Example\TCPServer\TCPServer\DataParser\JsonObjectEOFParser::class,
        // 固定包头分包数据处理器
        // 'dataParser'            => \Imi\SwooleTracker\Example\TCPServer\TCPServer\DataParser\JsonObjectFixedParser::class,
    ],

    // 子服务器（端口监听）配置
    'subServers'        => [
        // 'SubServerName'   =>  [
        //     'namespace'    =>    'Imi\SwooleTracker\Example\TCPServer\XXXServer',
        //     'type'        =>    Imi\Swoole\Server\Type::HTTP,
        //     'host'        =>    '127.0.0.1',
        //     'port'        =>    13005,
        // ]
    ],

    // 连接池配置
    'pools'             => [
    ],

    // 连接中心配置
    'connectionCenter' => [
        'redis_test'            => [
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
        'defaultPool'   => 'redis_test',
    ],

    // 内存表配置
    'memoryTable'       => [
        // 't1'    =>  [
        //     'columns'   =>  [
        //         ['name' => 'name', 'type' => \Swoole\Table::TYPE_STRING, 'size' => 16],
        //         ['name' => 'quantity', 'type' => \Swoole\Table::TYPE_INT],
        //     ],
        //     'lockId'    =>  'atomic',
        // ],
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
        ],
    ],

    // atmoic 配置
    'atomics'           => [
        // 'atomicLock'   =>  1,
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
