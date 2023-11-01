# AMQP

[toc]

## 介绍

支持在 imi 框架中使用 支持 AMQP 协议的消息队列，如：RabbitMQ

支持消息发布和消费

Github: <https://github.com/imiphp/imi-amqp>

## Composer

本项目可以使用composer安装，遵循psr-4自动加载规则，在你的 `composer.json` 中加入下面的内容:

```json
{
    "require": {
        "imiphp/imi-amqp": "~3.0.0"
    }
}
```

然后执行 `composer update` 安装。

## 使用说明

可以参考 `example` 目录示例，包括完整的消息发布和消费功能。

在项目 `config/config.php` 中配置：

```php
[
    'components'    =>  [
        // 引入组件
        'AMQP'   =>  'Imi\AMQP',
    ],
]
```

连接池配置：（Swoole）

```php
[
    'pools'    =>    [
        'rabbit'    =>  [
            'pool'    =>    [
                'class'        =>    \Imi\AMQP\Pool\AMQPCoroutinePool::class,
                'config'    =>    [
                    'maxResources'      => 10,
                    'minResources'      => 1,
                    'heartbeatInterval' => 30, // 连接池心跳时间，推荐设置
                ],
            ],
            'resource'    =>    [
                'host'            => '127.0.0.1',
                'port'            => 5672,
                'user'            => 'guest',
                'password'        => 'guest',
                'keepalive'       => false, // 截止 Swoole 4.8 还有兼容问题，所以必须设为 false，不影响使用
                'connectionClass' => \PhpAmqpLib\Connection\AMQPStreamConnection::class,
            ]
        ],
    ]
]
```

连接配置：（Workerman）

```php
'amqp' => [
    'connections' => [
        'rabbit'    => [
            'host'      => '127.0.0.1',
            'port'      => 5672,
            'user'      => 'guest',
            'password'  => 'guest',
        ],
    ],
],
```

默认连接池：

```php
[
    'beans' =>  [
        'AMQP'  =>  [
            'defaultPoolName'   =>  'rabbit',
        ],
    ],
]
```

### 连接配置项

| 属性名称 | 说明 |
|-|-
| host | 主机 |
| port | 端口 |
| user | 用户名 |
| vhost | vhost，默认 `/` |
| connectionTimeout | 连接超时 |
| readTimeout | 读超时 |
| writeTimeout | 写超时 |
| channelRpcTimeout | 频道 RPC 超时时间，默认 `0.0` |
| heartbeat | 心跳时间。如果不设置的情况，设置了连接池的心跳，就会设置为该值的 2 倍，否则设为`0` |
| keepalive | keepalive，默认 `false` |
| isSecure | 是否启用加密通信，默认 `false` |
| ioType | io 类型，默认 `stream`，可选：`stream`、`socket` |
| insist | insist |
| loginMethod | 默认 `AMQPLAIN` |
| loginResponse | loginResponse |
| locale | 默认 `en_US` |
| amqpProtocol | AMQP 协议，默认 `0.9.1` |
| protocolStrictFields | 是否使用严格的 AMQP 0.9.1 字段类型。RabbitMQ 不支持这个。默认 `false` |
| sendBufferSize | 发送缓冲区大小，默认 `0` |
| sslCaCert | CA 证书内容 |
| sslCaPath | CA 证书地址 |
| sslCert | SSL 证书 |
| sslKey | SSL 证书密钥 |
| sslVerify | 是否验证 SSL 证书 |
| sslVerifyName | SSL 证书验证名称 |
| sslPassPhrase | SSL 证书密码短语 |
| sslCiphers | SSL 密码 |
| sslSecurityLevel | SSL 安全等级 |
| isLazy | 是否懒加载，默认 `false`，不推荐修改 |
| networkProtocol | 网络协议，默认 `tcp`，不推荐修改 |
| streamContext | 流上下文，默认 `null`，不推荐修改 |
| dispatchSignals | 无用项，默认 `true`，不推荐修改 |
| connectionName | 连接名称，不推荐修改 |
| debugPackets | 输出所有网络数据包以进行调试。，默认 `false`，不推荐修改 |

### 队列组件支持

本组件额外实现了 [imiphp/imi-queue](https://github.com/imiphp/imi-queue) 的接口，可以用 Queue 组件的 API 进行调用。

只需要将队列驱动配置为：`KafkaQueueDriver`

配置示例：

```php
[
    'beans' =>  [
        'AutoRunProcessManager' =>  [
            'processes' =>  [
                // 加入队列消费进程，非必须，你也可以自己写进程消费
                'QueueConsumer',
            ],
        ],
        'imiQueue'  => [
            // 默认队列
            'default'   => 'QueueTest1',
            // 队列列表
            'list'  => [
                // 队列名称
                'QueueTest1' => [
                    // 使用的队列驱动
                    'driver'        => 'AMQPQueueDriver',
                    // 消费协程数量
                    'co'            => 1,
                    // 消费进程数量；可能会受进程分组影响，以同一组中配置的最多进程数量为准
                    'process'       => 1,
                    // 消费循环尝试 pop 的时间间隔，单位：秒（仅使用消费者类时有效）
                    'timespan'      => 0.1,
                    // 进程分组名称
                    'processGroup'  => 'a',
                    // 自动消费
                    'autoConsumer'  => true,
                    // 消费者类
                    'consumer'      => 'TestConsumer',
                    // 驱动类所需要的参数数组
                    'config'        => [
                        // AMQP 连接池名称
                        'poolName'      => 'rabbit',
                        // Redis 连接池名称
                        'redisPoolName' => 'redis',
                        // Redis 键名前缀
                        'redisPrefix'   => 'QueueTest1:',
                        // 可选配置：
                        // 支持消息删除功能，依赖 Redis
                        'supportDelete' => true,
                        // 支持消费超时队列功能，依赖 Redis，并且自动增加一个队列
                        'supportTimeout' => true,
                        // 支持消费失败队列功能，自动增加一个队列
                        'supportFail' => true,
                        // 循环尝试 pop 的时间间隔，单位：秒
                        'timespan'  => 0.03,
                        // 本地缓存的队列长度。由于 AMQP 不支持主动pop，而是主动推送，所以本地会有缓存队列，这个队列不宜过大。
                        'queueLength'   => 16,
                        // 消息类名
                        'message'   => \Imi\AMQP\Queue\JsonAMQPMessage::class,
                    ],
                ],
            ],
        ],
    ]
]
```

消费者类写法，与`imi-queue`组件用法一致。

### AMQP 消费发布

这个写法仅 AMQP 有效，其它消息队列不能这么写。

优点是可以完美利用 AMQP 特性，适合需要个性化定制的用户。

#### 消息定义

继承 `Imi\AMQP\Message` 类，可在构造方法中对属性修改。

根据需要可以覆盖实现`setBodyData`、`getBodyData`方法，实现自定义的消息结构。

```php
<?php
namespace ImiApp\AMQP\Test2;

use Imi\AMQP\Message;

class TestMessage2 extends Message
{
    /**
     * 用户ID
     *
     * @var int
     */
    private $memberId;

    /**
     * 内容
     *
     * @var string
     */
    private $content;

    public function __construct()
    {
        parent::__construct();
        $this->routingKey = 'imi-2';
        $this->format = \Imi\Util\Format\Json::class;
    }

    /**
     * 设置主体数据
     *
     * @param mixed $data
     * @return self
     */
    public function setBodyData($data)
    {
        foreach($data as $k => $v)
        {
            $this->$k = $v;
        }
    }

    /**
     * 获取主体数据
     *
     * @return mixed
     */
    public function getBodyData()
    {
        return [
            'memberId'  =>  $this->memberId,
            'content'   =>  $this->content,
        ];
    }

    /**
     * Get 用户ID
     *
     * @return int
     */ 
    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * Set 用户ID
     *
     * @param int $memberId  用户ID
     *
     * @return self
     */ 
    public function setMemberId(int $memberId)
    {
        $this->memberId = $memberId;

        return $this;
    }

    /**
     * Get 内容
     *
     * @return string
     */ 
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set 内容
     *
     * @param string $content  内容
     *
     * @return self
     */ 
    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }

}
```

**属性列表：**

名称 | 说明 |  默认值
-|-|-
bodyData | 消息主体内容，非字符串 | `null` |
properties | 属性 | `['content_type'  => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,]` |
routingKey | 路由键 | 空字符串 |
format | 如果设置了，发布的消息是编码后的`bodyData`，同理读取时也会解码。实现了`Imi\Util\Format\IFormat`的格式化类。支持`Json`、`PhpSerialize` | `null` |
mandatory | mandatory标志位 | `false` |
immediate | immediate标志位 | `false` |
ticket | ticket | `null` |

#### 发布者定义

必选注解：`@Publisher`

可选注解：`@Queue`、`@Exchange`、`@Connection`

不配置 `@Connection` 注解，从默认连接池中获取连接

```php
<?php
namespace ImiApp\AMQP\Test;

use Imi\Bean\Annotation\Bean;
use Imi\AMQP\Annotation\Queue;
use Imi\AMQP\Base\BasePublisher;
use Imi\AMQP\Annotation\Consumer;
use Imi\AMQP\Annotation\Exchange;
use Imi\AMQP\Annotation\Publisher;
use Imi\AMQP\Annotation\Connection;

#[
    Bean(name: 'TestPublisher'),
    Connection(poolName: null),
    Publisher(tag: 'tag-imi', queue: 'queue-imi-1', exchange: 'exchange-imi', routingKey: 'imi-1'),
    Queue(name: 'queue-imi-1', routingKey: 'imi-1'),
    Exchange(name: 'exchange-imi')
]
class TestPublisher extends BasePublisher
{

}
```

#### 发布消息

```php
// 实例化构建消息
$message = new \ImiApp\AMQP\Test2\TestMessage2;
$message->setMemberId(1);
$message->setContent('imi niubi');

// 发布消息
/** @var \ImiApp\AMQP\Test\TestPublisher $testPublisher */
$testPublisher = \Imi\RequestContext::getBean('TestPublisher');
// 请勿使用 App::getBean()、@Inject 等全局单例注入
// $testPublisher = App::getBean('TestPublisher');
$testPublisher->publish($message);
```

### 消费者定义

必选注解：`@Consumer`

可选注解：`@Queue`、`@Exchange`、`@Connection`

不配置 `@Connection` 注解，从默认连接池中获取连接

```php
<?php
namespace ImiApp\AMQP\Test;

use Imi\Redis\Redis;
use Imi\Bean\Annotation\Bean;
use Imi\AMQP\Annotation\Queue;
use Imi\AMQP\Base\BaseConsumer;
use Imi\AMQP\Contract\IMessage;
use Imi\AMQP\Annotation\Consumer;
use Imi\AMQP\Annotation\Exchange;
use Imi\AMQP\Enum\ConsumerResult;
use Imi\AMQP\Annotation\Connection;

/**
 * 启动一个新连接消费
 */
#[
    Bean(name: 'TestConsumer'),
    Connection(poolName: null),
    Consumer(tag: "tag-imi", queue: "queue-imi-1", message: \ImiApp\AMQP\Test\TestMessage::class)
]
class TestConsumer extends BaseConsumer
{
    /**
     * 消费任务
     *
     * @param \ImiApp\AMQP\Test\TestMessage $message
     */
    protected function consume(IMessage $message): int
    {
        var_dump(__CLASS__, $message->getBody(), get_class($message));
        Redis::set('imi-amqp:consume:1:' . $message->getMemberId(), $message->getBody());
        return ConsumerResult::ACK;
    }

}

```

#### 消费消息

##### 随服务启动的消费进程

只会启动一个进程，适合量少的场景。适合IO密集型场景。

首先定义进程：

```php
<?php
namespace ImiApp\Process;

use Imi\Swoole\Process\BaseProcess;
use Imi\Aop\Annotation\Inject;
use Imi\Swoole\Process\Annotation\Process;

#[Process(name: 'TestProcess')]
class TestProcess extends BaseProcess
{
    /**
     * @var \ImiApp\AMQP\Test\TestConsumer
     */
    #[Inject(name: 'TestConsumer')]
    protected $testConsumer;

    /**
     * @var \ImiApp\AMQP\Test2\TestConsumer2
     */
    #[Inject(name: 'TestConsumer2')]
    protected $testConsumer2;

    public function run(\Swoole\Process $process): void
    {
        // 启动消费者
        go(function(){
            do {
                $this->testConsumer->run();
            } while(true);
        });
        go(function(){
            do {
                $this->testConsumer2->run();
            } while(true);
        });
    }

}
```

然后在项目配置`@app.beans`中配置消费进程

```php
[
    'AutoRunProcessManager' =>  [
        'processes' =>  [
            'TestProcess'
        ],
    ],
]
```

##### 启动进程池消费

适合计算密集型场景、消费量非常多的场景。

进程池写法参考：[链接](/v3.0/components/process-pool/swoole.html)

启动消费者写法参考上面的即可。

#### 注解说明

#### @Publisher

发布者注解

| 属性名称 | 说明 |
|-|-
| queue | 队列名称 |
| exchange | 交换机名称 |
| routingKey | 路由键 |

#### @Consumer

消费者注解

| 属性名称 | 说明 |
|-|-
| tag | 消费者标签 |
| queue | 队列名称 |
| exchange | 交换机名称 |
| routingKey | 路由键 |
| message | 消息类名，默认：`Imi\AMQP\Message` |
| mandatory | mandatory标志位 |
| immediate | immediate标志位 |
| ticket | ticket |

#### @Queue

队列注解

| 属性名称 | 说明 |
|-|-
| name | 队列名称 |
| routingKey | 路由键 |
| passive | 被动模式，默认`false` |
| durable | 消息队列持久化，默认`true` |
| exclusive | 独占，默认`false` |
| autoDelete | 自动删除，默认`false` |
| nowait | 是否非阻塞，默认`false` |
| arguments | 参数 |
| ticket | ticket |

#### @Exchange

交换机注解

| 属性名称 | 说明 |
|-|-
| name | 交换机名称 |
| type | 类型可选：`direct`、`fanout`、`topic`、`headers` |
| passive | 被动模式，默认`false` |
| durable | 消息队列持久化，默认`true` |
| autoDelete | 自动删除，默认`false` |
| internal | 设置是否为rabbitmq内部使用, `true`表示是内部使用, `false`表示不是内部使用 |
| nowait | 是否非阻塞，默认`false` |
| arguments | 参数 |
| ticket | ticket |

#### @Connection

连接注解

| 属性名称 | 说明 |
|-|-
| poolName | 不为 `null` 时，无视其他属性，直接用该连接池配置。默认为`null`，如果`host`、`port`、`user`、`password`都未设置，则获取默认的连接池。 |

## 使用示例

### 延时消息

支付宝、微信支付都有一个逻辑，就是用户支付成功后会通过 HTTP 请求来通知我们的接口。

接口如果没有按照约定的格式返回成功，会定时重试N次，每次延时不同，直到超过一定次数后，就不再重试。

下面是一个支付通知的消费者示例：

**消费者类：**

```php
<?php

namespace PayService\Module\Pay\AMQP\PayNotify;

use Imi\AMQP\Annotation\Consumer;
use Imi\AMQP\Annotation\Exchange;
use Imi\AMQP\Annotation\Queue;
use Imi\AMQP\Base\BaseConsumer;
use Imi\AMQP\Contract\IMessage;
use Imi\AMQP\Enum\ConsumerResult;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\Annotation\Bean;
use Imi\Bean\BeanFactory;
use Imi\Log\Log;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

#[
    Bean(name: 'PayNotifyConsumer'),
    Exchange(name: 'exchange-pay-notify'),
    Queue(name: 'pay-notify', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify-dead']),

    Exchange(name: 'exchange-pay-notify-step-1'),
    Queue(name: 'pay-notify-step-1', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 15000]),
    Exchange(name: 'exchange-pay-notify-step-2'),
    Queue(name: 'pay-notify-step-2', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 15000]),
    Exchange(name: 'exchange-pay-notify-step-3'),
    Queue(name: 'pay-notify-step-3', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 30000]),
    Exchange(name: 'exchange-pay-notify-step-4'),
    Queue(name: 'pay-notify-step-4', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 180000]),
    Exchange(name: 'exchange-pay-notify-step-5'),
    Queue(name: 'pay-notify-step-5', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 600000]),
    Exchange(name: 'exchange-pay-notify-step-6'),
    Queue(name: 'pay-notify-step-6', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 1200000]),
    Exchange(name: 'exchange-pay-notify-step-7'),
    Queue(name: 'pay-notify-step-7', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 1800000]),
    Exchange(name: 'exchange-pay-notify-step-8'),
    Queue(name: 'pay-notify-step-8', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 1800000]),
    Exchange(name: 'exchange-pay-notify-step-9'),
    Queue(name: 'pay-notify-step-9', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 1800000]),
    Exchange(name: 'exchange-pay-notify-step-10'),
    Queue(name: 'pay-notify-step-10', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 3600000]),
    Exchange(name: 'exchange-pay-notify-step-11'),
    Queue(name: 'pay-notify-step-11', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 10800000]),
    Exchange(name: 'exchange-pay-notify-step-12'),
    Queue(name: 'pay-notify-step-12', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 10800000]),
    Exchange(name: 'exchange-pay-notify-step-13'),
    Queue(name: 'pay-notify-step-13', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 10800000]),
    Exchange(name: 'exchange-pay-notify-step-14'),
    Queue(name: 'pay-notify-step-14', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 21600000]),
    Exchange(name: 'exchange-pay-notify-step-15'),
    Queue(name: 'pay-notify-step-15', arguments: ['x-dead-letter-exchange' => 'exchange-pay-notify', 'x-message-ttl' => 21600000]),

    Consumer(tag: 'payNotify', queue: 'pay-notify', exchange: 'exchange-pay-notify', message: \PayService\Module\Pay\AMQP\PayNotify\PayNotifyMessage::class),
]
class PayNotifyConsumer extends BaseConsumer
{
    /**
     * 定义消费者.
     *
     * @return void
     */
    protected function declareConsumer(): void
    {
        // 定义死信
        $this->channel->exchange_declare('exchange-pay-notify-dead', AMQPExchangeType::DIRECT, false, true, false);
        $this->channel->queue_declare('pay-notify-dead', false, true, false, false);
        $this->channel->queue_bind('pay-notify-dead', 'exchange-pay-notify-dead');
        $this->channel->queue_bind('pay-notify-dead', 'exchange-pay-notify-dead', 'pay-notify');
        $this->channel->queue_bind('pay-notify-dead', 'exchange-pay-notify-dead', 'pay-notify-dead');
        parent::declareConsumer();
    }

    /**
     * 消费任务
     *
     * @param \PayService\Module\Pay\AMQP\PayNotify\PayNotifyMessage $message
     *
     * @return void
     */
    protected function consume(IMessage $message)
    {
        try
        {
            // 是否需要重试
            $needRetry = false;
            // HTTP通知逻辑
            // ...
            // $needRetry = true; // 通知失败，需要重试
        }
        // 你也可以定义一个用于重试的异常，在上面逻辑中抛出
        // catch (RetryException $re)
        // {
        //     $needRetry = true;
        // }
        catch (\Throwable $th)
        {
            throw $th;
        }
        finally
        {
            if (isset($th))
            {
                $result = ConsumerResult::REJECT;
            }
            elseif ($needRetry)
            {
                $stepCount = \count(AnnotationManager::getClassAnnotations(BeanFactory::getObjectClass($this), Queue::class));
                if ($message->getRetryCount() < $stepCount - 1)
                {
                    $newMessage = clone $message;
                    $step = $message->getRetryCount() + 1;
                    $newMessage->setRetryCount($step);
                    $amqpMessage = $newMessage->getAMQPMessage();
                    $amqpMessage->set('delivery_mode', AMQPMessage::DELIVERY_MODE_PERSISTENT);
                    $amqpMessage->setBody($newMessage->getBody());
                    $queueName = 'pay-notify-step-' . $step;
                    $exchangeName = 'exchange-pay-notify-step-' . $step;
                    $this->channel->queue_bind($queueName, $exchangeName);
                    $this->channel->basic_publish($amqpMessage, $exchangeName);
                }
                else
                {
                    $result = ConsumerResult::REJECT;
                }
            }
        }

        return $result ?? ConsumerResult::ACK;
    }
}
```

**PayNotifyMessage：**

```php
<?php

namespace PayService\Module\Pay\AMQP\PayNotify;

use Imi\AMQP\Message;

class PayNotifyMessage extends Message
{
    /**
     * 支付订单ID.
     *
     * @var int
     */
    private $payOrderId;

    /**
     * 延时多少秒执行.
     *
     * @var int
     */
    private $retryCount = 0;

    public function __construct()
    {
        parent::__construct();
        $this->format = \Imi\Util\Format\Json::class;
    }

    /**
     * 设置主体数据.
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setBodyData($data): self
    {
        foreach ($data as $k => $v)
        {
            $this->$k = $v;
        }

        return $this;
    }

    /**
     * 获取主体数据.
     *
     * @return mixed
     */
    public function getBodyData()
    {
        return [
            'payOrderId'    => $this->payOrderId,
            'retryCount'    => $this->retryCount,
        ];
    }

    /**
     * Get 支付订单ID.
     *
     * @return int
     */
    public function getPayOrderId()
    {
        return $this->payOrderId;
    }

    /**
     * Set 支付订单ID.
     *
     * @param int $payOrderId 支付订单ID
     *
     * @return self
     */
    public function setPayOrderId(int $payOrderId)
    {
        $this->payOrderId = $payOrderId;

        return $this;
    }

    /**
     * Get 延时多少秒执行.
     *
     * @return int
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * Set 延时多少秒执行.
     *
     * @param int $retryCount 延时多少秒执行
     *
     * @return self
     */
    public function setRetryCount(int $retryCount)
    {
        $this->retryCount = $retryCount;

        return $this;
    }
}
```
