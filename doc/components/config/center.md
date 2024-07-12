# 配置中心

[toc]

我们通常会把一些配置放到专门的配置文件里，一般会随着代码分发和部署。

在需要修改配置的时候，需要重新走发版流程，显得非常笨重和不方便。

这时候，配置中心的作用就体现了。

我们从配置中心拿配置，而不是写死在项目里，可以比较灵活地实现不中断服务的配置发布。

**支持的配置中心：**

* [x] Nacos ([imi-nacos](https://github.com/imiphp/imi-nacos))

* [ ] Apollo

* [x] etcd ([imi-etcd](https://github.com/imiphp/imi-etcd)) ([@ChrisLeeAreemm](https://github.com/ChrisLeeAreemm))

* [ ] Consul

* [x] Zookeeper ([imi-zookeeper](https://github.com/imiphp/imi-zookeeper))

**支持的容器：**

* Swoole

* Workerman

## 设计

### 监听模式

#### 工作进程模式

每个进程自己监听，适用于 Swoole 环境。

#### 进程模式

由一个专门的进程监听，并通知到其它进程。适用于 Swoole、Workerman 环境。

对配置中心压力更小。

---

> php-fpm 模式比较特殊，是走的文件缓存逻辑。超过一定时间才去请求配置中心，获取数据，实时性有一定影响。

### 监听方式

#### 客户端轮询

客户端定时请求配置中心，对配置中心服务端压力较大，但是最为通用。

#### 服务端推送（长轮询）

如果配置中心支持服务端推送（长轮询），建议用这个方式，对配置中心服务端压力较小。

## 使用

**配置中心必须安装：**`composer require imiphp/imi-config-center:2.1.x-dev` 

### Nacos

**安装：**`composer require imiphp/imi-nacos:2.1.x-dev`

**配置：**

`@app.beans`：

```php
use Imi\Util\Imi;
[
    'ConfigCenter' => [
        // 'mode'    => \Imi\ConfigCenter\Enum\Mode::WORKER, // 工作进程模式
        'mode'    => \Imi\ConfigCenter\Enum\Mode::PROCESS, // 进程模式
        'configs' => [
            'nacos' => [
                'driver'  => \Imi\Nacos\Config\NacosConfigDriver::class,
                // 客户端连接配置
                'client'  => [
                    'host'                => '127.0.0.1', // 主机名
                    'port'                => 8848, // 端口号
                    'prefix'              => '/', // 前缀
                    'username'            => 'nacos', // 用户名
                    'password'            => 'nacos', // 密码
                    'timeout'             => 60000, // 网络请求超时时间，单位：毫秒
                    'ssl'                 => false, // 是否使用 ssl(https) 请求
                    'authorizationBearer' => false, // 是否使用请求头 Authorization: Bearer {accessToken} 方式传递 Token，旧版本 Nacos 需要设为 true
                ],
                // 监听器配置
                'listener' => [
                    'timeout'         => 30000, // 配置监听器长轮询超时时间，单位：毫秒
                    'failedWaitTime'  => 3000, // 失败后等待重试时间，单位：毫秒
                    'savePath'        => Imi::getRuntimePath('config-cache'), // 配置保存路径，默认为空不保存到文件。php-fpm 模式请一定要设置！
                    'fileCacheTime'   => 30, // 文件缓存时间，默认为0时不受缓存影响，此配置只影响 pull 操作。php-fpm 模式请一定要设置为大于0的值！
                    'pollingInterval' => 10000, // 客户端轮询间隔时间，单位：毫秒
                ],
                // 配置项
                'configs' => [
                    'nacos' => [
                        'key'   => 'imi-nacos-key1',
                        'group' => 'imi',
                        'type'  => 'json', // 配置内容类型，Nacos >= 1.3 可以不配，由配置项类型智能指定
                    ],
                ],
            ],
        ],
    ],
]
```

**获取配置：**

```php
\Imi\Config::get('nacos'); // 对应 imi-nacos-key1
```

**写入配置：**

```php
/** @var \Imi\ConfigCenter\ConfigCenter $configCenter */
$configCenter = App::getBean('ConfigCenter');
$name = 'imi-nacos-key1';
$group = 'imi';
$type = 'json';
$value = json_encode(['imi' => 'niubi']);
$configCenter->getDriver('nacos')->push($name, $value, [
    'group' => $group,
    'type'  => $type,
]);
```

### Etcd

**安装：**`composer require imiphp/imi-etcd:2.1.x-dev`

**配置：**

`@app.beans`：

```php
use function Imi\env;
use Imi\Util\Imi;

[
    'ConfigCenter' => [
        // 'mode'    => \Imi\ConfigCenter\Enum\Mode::WORKER, // 工作进程模式
        'mode'    => \Imi\ConfigCenter\Enum\Mode::PROCESS, // 进程模式
        'configs' => [
            'etcd' => [
                'driver'  => \Imi\Etcd\Config\EtcdConfigDriver::class,
                // 客户端连接配置
                'client'  => [
                    'scheme'              => env('IMI_ETCD_SCHEME', 'http'), // 主机名
                    'host'                => env('IMI_ETCD_HOST', '127.0.0.1'), // 主机名
                    'port'                => env('IMI_ETCD_PORT', 2379), // 端口号
                    'timeout'             => env('IMI_ETCD_TIMEOUT', 6000), // 网络请求超时时间，单位：毫秒
                    'ssl'                 => env('IMI_ETCD_SSL', false), // 是否使用 ssl(https) 请求
                    'version'             => env('IMI_ETCD_VERSION', 'v3'), /**
                     * v3 v3alpha v3beta v2
                     * etcd v3.2以及之前版本只使用[CLIENT-URL]/v3alpha/*。
                     * etcd v3.3使用[CLIENT-URL]/v3beta/*保持[CLIENT-URL]/v3alpha/*使用。
                     * etcd v3.4使用[CLIENT-URL]/v3/*保持[CLIENT-URL]/v3beta/*使用。
                     * [CLIENT-URL]/v3alpha/*被抛弃使用。
                     * etcd v3.5以及最新版本只使用[CLIENT-URL]/v3/*。
                     * [CLIENT-URL]/v3beta/*被抛弃使用。
                     */
                    'pretty'              => env('IMI_ETCD_PRETTY', true),
                    'sslCert'             => '',
                    'sslKey'              => ''
                ],
                // 监听器配置
                'listener' => [
                    'timeout'         => 30000, // 配置监听器长轮询超时时间，单位：毫秒
                    'failedWaitTime'  => 3000, // 失败后等待重试时间，单位：毫秒
                    'savePath'        => Imi::getRuntimePath('config-cache'), // 配置保存路径，默认为空不保存到文件。php-fpm 模式请一定要设置！
                    'fileCacheTime'   => 30, // 文件缓存时间，默认为0时不受缓存影响，此配置只影响 pull 操作。php-fpm 模式请一定要设置为大于0的值！
                    'pollingInterval' => 10000, // 客户端轮询间隔时间，单位：毫秒
                ],
                // 配置项
                'configs' => [
                    'etcd' => [
                        'key'  => 'imi-etcd-key1',
                    ],
                ],
            ],
        ],
    ],
]
```

**获取配置：**

```php
\Imi\Config::get('etcd'); // 对应 imi-etcd-key1
```

**写入配置：**

```php
/** @var \Imi\ConfigCenter\ConfigCenter $configCenter */
$configCenter = App::getBean('ConfigCenter');
$name = 'imi-etcd-key1';
$value = json_encode(['imi' => 'niubi']);
$options = [];
$configCenter->getDriver('etcd')->push($name, $value);
$configCenter->getDriver('etcd')->push($name, $value, $options);
```

### ZooKeeper

* Swoole 用户请安装 [swoole-zookeeper](https://github.com/swoole/ext-zookeeper) 扩展。

* 非 Swoole 用户请安装 [php-zookeeper](<https://github.com/php-zookeeper/php-zookeeper>) 扩展。

**安装：**`composer require imiphp/imi-zookeeper:2.1.x-dev`

**配置：**

`@app.beans`：

```php
use function Imi\env;
use Imi\Util\Imi;

[
    'ConfigCenter' => [
        // 'mode'    => \Imi\ConfigCenter\Enum\Mode::WORKER, // 工作进程模式
        'mode'    => \Imi\ConfigCenter\Enum\Mode::PROCESS, // 进程模式
        'configs' => [
            'zookeeper' => [
                'driver'  => \Imi\ZooKeeper\Config\SwooleZooKeeperConfigDriver::class, // Swoole 驱动
                // 'driver'  => \Imi\ZooKeeper\Config\ZooKeeperConfigDriver::class, // 非 Swoole 驱动
                // 客户端连接配置
                'client'  => [
                    'host'    => env('IMI_ZOOKEEPER_HOST', '127.0.0.1:2181'), // 主机名:端口
                    'timeout' => 10, // 网络请求超时时间，单位：秒
                ],
                // 监听器配置
                'listener' => [
                    'timeout'         => 30000, // 配置监听器长轮询超时时间，单位：毫秒
                    'failedWaitTime'  => 3000, // 失败后等待重试时间，单位：毫秒
                    'savePath'        => Imi::getRuntimePath('config-cache'), // 配置保存路径，默认为空不保存到文件。php-fpm 模式请一定要设置！
                    'fileCacheTime'   => 30, // 文件缓存时间，默认为0时不受缓存影响，此配置只影响 pull 操作。php-fpm 模式请一定要设置为大于0的值！
                    'pollingInterval' => 10000, // 客户端轮询间隔时间，单位：毫秒
                ],
                // 配置项
                'configs' => [
                    'zookeeper' => [
                        'key'   => 'imi-zooKeeper-key1',
                        'type'  => 'json', // 配置内容类型
                    ],
                ],
            ],
        ],
    ],
]
```

**获取配置：**

```php
\Imi\Config::get('zookeeper'); // 对应 imi-zooKeeper-key1
```

**写入配置：**

```php
/** @var \Imi\ConfigCenter\ConfigCenter $configCenter */
$configCenter = App::getBean('ConfigCenter');
$name = 'imi-zooKeeper-key1';
$value = json_encode(['imi' => 'niubi']);
$configCenter->getDriver('zookeeper')->push($name, $value);
```

## 开发配置中心驱动

上面的 imi 配置中心组件，都是基于 [imi-config-center](https://github.com/imiphp/imi-config-center) 开发的，你可以参考他们的代码进行开发实现其它配置中心驱动。
