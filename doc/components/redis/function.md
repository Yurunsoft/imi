# Redis 使用

[toc]

## 基础使用

> 注意，方法和传参在不同客户端中可能存在不一致性，具体参考各自客户端文档

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

### LuaScript类

imi 提供的通过类定义 LUA 脚本的方式，可以更好的管理和复用 LUA 脚本。

***普通定义***

```php
use Imi\Redis\RedisLuaScript;

class TestEvalScript1 extends RedisLuaScript
{
    public function keysNum(): int
    {
        // 定义脚本需要的 key 数量
        return 2;
    }

    public function script(): string
    {
        // 定义脚本内容
        return <<<LUA
            local key = KEYS[1]
            local value = ARGV[1]
            redis.call('set', key, value)
            return redis.call('get', key)
            LUA;
    }
}

// ==== 使用 ====

use Imi\Redis\IRedisHandler;

/** @var IRedisHandler $redis */

$script = new TestEvalScript1();
$result = $script->invoke($redis, ['imi-script:key1', 'imi-script:key2'], 'val1', 'val2', 'val3');

// 对脚本进行只读执行 （PS: 因例子脚本中使用`set`，只读调用将会导致报错）
$script->withReadOnly()->invoke($redis, ['imi-script:key1', 'imi-script:key2'], 'val4', 'val5');
```

***快速定义并使用***

```php
use Imi\Redis\RedisLuaScript;

/** @var IRedisHandler $redis */

$script = RedisLuaScript::fastCreate(
    script: <<<'LUA'
    -- 从工作队列删除
    redis.call('zrem', KEYS[1], ARGV[1])
    redis.call('rpush', KEYS[2], ARGV[1])
    return true
    LUA,
    keyNum: 2,
);
$script->invoke($redis, ['{imi:work}:queue', '{imi:work}:doing']);
```

> 注意**cluster**模式下使用时必须确保所以`key`都命中同一`node`，否则会导致执行失败。

> 注意**只读执行**环境条件:
> - redis >= 7.0
> - phpredis >= 6.0
> - predis > 2.2.2 (版本`<= 2.2.2`的`cluster`模式存在`bug`，待扩展新版本发布才能使用)

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
