# Redis

[toc]

Redis 连接池配置方式已经在连接池里讲过，这里就不重复了，直接说使用方法。

## 环境需求

- [phpredis](https://github.com/phpredis/phpredis) **主要支持**, 最小版本: `>= 5.3.2`, 推荐版本: `>= 5.3.7`
- [predis](https://github.com/predis/predis) **备用选项**, 最小版本: `>= 2.0`

## 连接配置

> 从 imi v3.0 版本开始支持

```php

<?php
return [
    'redis'    => [
        'defaultPool' => 'test_redis1', // 默认连接池
    ],
    [
    'connectionCenter' => [
        // 标准单单节点连接
        // test_redis1 是连接名称
        'test_redis1' => [
            // 连接管理器类名
            // Swoole 推荐用连接池
            'manager' => \Imi\ConnectionCenter\Handler\Pool\PoolConnectionManager::class,
            // 非 Swoole 环境建议使用连接上下文单例。更多连接管理器可前往连接中心文档查阅。
            // 'manager' => \Imi\ConnectionCenter\Handler\RequestContextSingleton\RequestContextSingletonConnectionManager::class,

            // 连接管理器配置
            'config'  => [
                // 连接驱动类名，此处数据库固定写法
                'driver'    => \Imi\Redis\Connector\RedisConnectionDriver::class,
                // 连接配置 (详细设置参考下方配置详情)
                'resources' => [
                    [
                        'client' => 'phpredis',
                        'mode'   => \Imi\Redis\Enum\RedisMode::Standalone,
                        'host'      => env('REDIS_SERVER_HOST', '127.0.0.1'),
                        'port'      => env('REDIS_SERVER_PORT', 6379),
                        'password'  => env('REDIS_SERVER_PASSWORD'),
                    ],
                ],
                // 是否启用统计，启用后可能会有微量性能损耗
                'enableStatistics' => false,
                // 当前请求上下文资源检查状态间隔，单位：支持小数的秒。为 null/0 则每次都检查
                'requestResourceCheckInterval' => null,
                // 是否在获取资源时检查状态
                'checkStateWhenGetResource' => false,
            ],
        ],
        // Cluster 连接例子
        'test_cluster' => [
            'manager' => \Imi\ConnectionCenter\Handler\Pool\PoolConnectionManager::class,
            'config'  => [
                'driver'    => \Imi\Redis\Connector\RedisConnectionDriver::class,
                'resources' => [
                    [
                        'client'   => 'phpredis',
                        'mode'     => \Imi\Redis\Enum\RedisMode::Cluster,
                        'password' => env('REDIS_SERVER_CLUSTER_PASSWORD'),
                        'seeds'    => [
                            '172.10.10.2:6443',
                            '172.10.10.3:6443',
                            '172.10.10.4:6443',
                            '172.10.10.5:6443',
                            '172.10.10.6:6443',
                            '172.10.10.7:6443',
                        ],
                    ],
                ],
            ],
        ],
        // tls 连接例子
        'test_tls' => [
            'manager' => \Imi\ConnectionCenter\Handler\Pool\PoolConnectionManager::class,
            'config'  => [
                'driver'    => \Imi\Redis\Connector\RedisConnectionDriver::class,
                'resources' => [
                    [
                        'client'    => 'phpredis',
                        'mode'      => \Imi\Redis\Enum\RedisMode::Standalone,
                        'scheme'    => 'tls',
                        'host'      => env('REDIS_SERVER_HOST', '127.0.0.1'),
                        'port'      => env('REDIS_SERVER_PORT', 6379),
                        'password'  => env('REDIS_SERVER_PASSWORD'),
                        'tls'       => [
                            // https://www.php.net/context.ssl
                            'verify_peer_name' => false,
                            'cafile'           => env('REDIS_SERVER_TLS_CA_FILE'),
                            'local_cert'       => env('REDIS_SERVER_TLS_CERT_FILE'),
                            'local_pk'         => env('REDIS_SERVER_TLS_KEY_FILE'),
                        ],
                    ],
                ],
            ],
        ],
    ]
]
```

## 资源选项

| 参数名         | 类型           | 默认值                                   | 说明                                                          |
|-------------|--------------|---------------------------------------|-------------------------------------------------------------|
| client      | string       | phpredis, predis                      | 客户端驱动                                                       |
| mode        | string       | \Imi\Redis\Enum\RedisMode::Standalone | 工作模式 RedisMode::(Standalone, Cluster, Sentinel)             |         |
| scheme      | string       | tcp                                   | 连接协议（`tcp`, `unix`, `tls`）                                  |
| host        | string       | 127.0.0.1                             | 主机地址                                                        |
| port        | int          | 6379                                  | 端口                                                          |
| seeds       | array        | null                                  | 集群节点（仅对`Cluster`模型下有效）                                      |
| password    | string,array | ''                                    | 字符串密码、ACL:`['user' => 'root', 'pass' => 'pwd123456']`       |
| database    | int          | 0                                     | 使用第几个库（建议只使用`0`）                                            |
| prefix      | string       | ''                                    | `key`统一前缀                                                   |
| timeout     | float        | 3.0                                   | 连接超时                                                        |
| readTimeout | float        | 3.0                                   | 读取超时（`predis`下也作为写入超时）                                      |
| serialize   | bool         | true                                  | 启用自动序列化（`predis`下不支持），关闭后写入值将只支持字符串。有条件下推荐关闭并自行序列化写入值。      |
| options     | array        | `[]`                                  | 客户端配置（仅对`phpredis`客户端有效）                                    |
| tls         | array        | null                                  | `tls`配置，配置参考 [ssl context](https://www.php.net/context.ssl) |

## 客户端与驱动

| 客户端      | 驱动类                                         | 内置序列化 | 首选 | 说明                  |
|----------|---------------------------------------------|-------|----|---------------------|
| phpredis | `\Imi\Redis\Handler\PhpRedisHandler`        | ✔     | ✔  |                     |
| phpredis | `\Imi\Redis\Handler\PhpRedisClusterHandler` | ✔     | ✔  |                     |
| predis   | `\Imi\Redis\Handler\PredisHandler`          | ✘     | ✘  | 不内建序列化，部分组件可能存在兼容问题 |
| predis   | `\Imi\Redis\Handler\PredisClusterHandler`   | ✘     | ✘  | 不内建序列化，部分组件可能存在兼容问题 |

> 注意，不客户端的使用是有差异的，无法无损代替，项目确定客户端后不建议随意切换。
> 框架首选支持客户端为`phpredis`，使用`predis`时可能会存在因为用法不一致导致的兼容问题。

## 基本使用

与原生 Redis 类操作方式基本一致，请参考具体客户端各自的文档。

- [phpredis](https://github.com/phpredis/phpredis)
- [predis](https://github.com/predis/predis)

## TODO

> 支持资源URL 'tcp://192.168.0.222?timeout=60&db=1;tcp://192.168.0.222', 'unix:///var/run/redis/redis-server.sock?db=1'
> 支持 ACL 鉴权
> Sentinel 模式实现
> 完善的传参验证
