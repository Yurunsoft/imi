# Redis 使用

[toc]

## 基础使用

**注意，方法和传参在不同客户端中可能存在不一致性，具体参考各自客户端文档**

### 获取连接对象

```php
use \Imi\Redis\RedisManager;

/** @var \Imi\Redis\Handler\PhpRedisHandler $redis */
$redis = RedisManager::getInstance();

// 获取到`$redis`返回值为实现`Imi\Redis\Handler\IRedisHandler`的具体驱动
// 建议根据实际情况使用注解或强类型把`$redis`的类型限制为`Imi\Redis\Handler\IRedisHandler`具体实现驱动以活动更好的`IDE`提示支持
// 具体驱动有:
// \Imi\Redis\Handler\PhpRedisHandler
// \Imi\Redis\Handler\PhpRedisClusterHandler
// \Imi\Redis\Handler\PredisHandler
// \Imi\Redis\Handler\PredisClusterHandler

$redis->set('imi:redis:test', date('Y-m-d H:i:s'));
$datetime = $redis->get('imi:redis:test');
```

### 获取新连接对象

每次调用都尝试从连接池中获取新的对象！

```php
use \Imi\Redis\RedisManager;
$redis = RedisManager::getNewInstance();
$redis->get('key-xxx')
```

### 获取默认连接池名称

```php
use \Imi\Redis\RedisManager;
echo RedisManager::getDefaultPoolName();
```

### 便捷操作（不建议使用）

> **不建议使用该模式，`ide`提示无法完善**

`Redis::方法名()`

```php
use \Imi\Redis\Redis;
Redis::set('imi:redis:test', date('Y-m-d H:i:s'));
$datetime = Redis::get('imi:redis:test');
```

### 回调方式使用`Redis`

```php
use \Imi\Redis\Redis;
$result = Redis::use(function(Imi\Redis\Handler\IRedisHandler $redis){
    $redis->set('a', 1);
    return true;
}); // true
```

## 进阶使用

### evalEx

imi 封装了一个基于 `evalSha` 和 `eval` 的便捷方法，优先使用 `evalSha` 尝试，失败则使用 `eval` 方法。

定义：`public function evalEx($script, $args = null, $num_keys = null)`

```php
use \Imi\Redis\RedisManager;
/** @var \Imi\Redis\Handler\PhpRedisHandler $redis */
$redis = RedisManager::getInstance();
return false !== $redis->evalEx(<<<SCRIPT
redis.call('set', 'a', '123')
return redis.call('get', 'a')
SCRIPT
    );
```

### SCAN系列方法

#### 主要用法

```php
use \Imi\Redis\RedisManager;

/** @var \Imi\Redis\Handler\PhpRedisHandler $redis */
$redis = RedisManager::getInstance();

// 通用封装（推荐）
$redis->scanEach();
$redis->hscanEach();
$redis->sscanEach();
$redis->zscanEach();

// 传统调用（传参在各客户端中存在一定差距，具体参考文档）
$redis->scan();
$redis->hscan();
$redis->sscan();
$redis->zscan();
```

#### 静态调用模式

> **不建议使用，无法完美兼容多个客户端，计划弃用**

```php
\Imi\Redis::scan()
\Imi\Redis::hscan()
\Imi\Redis::sscan()
\Imi\Redis::zscan()
```

#### scanEach 参数定义

##### scanEach

`scan` 方法的扩展简易遍历方法

参数与 `scan` 基本一致，无需传递 `it` 参数

```php
foreach($redis->scanEach('imi:scanEach:*', 10) as $value)
{
    var_dump($value);
}
```

##### hscanEach

`hscan` 方法的扩展简易遍历方法

参数与 `hscan` 基本一致，无需传递 `it` 参数

```php
foreach($redis->hscanEach($key, 'value:*', 10) as $k => $value)
{
    var_dump($k, $value);
}
```

##### sscanEach

`sscan` 方法的扩展简易遍历方法

参数与 `sscan` 基本一致，无需传递 `it` 参数

```php
foreach($redis->sscanEach($key, '*', 10) as $value)
{
    var_dump($value);
}
```

##### zscanEach

`zscan` 方法的扩展简易遍历方法

参数与 `zscan` 基本一致，无需传递 `it` 参数

```php
foreach($redis->zscanEach($key, '*', 10) as $score)
{
    var_dump($value);
}
```
