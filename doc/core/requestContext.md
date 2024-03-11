# 请求上下文

[toc]

请求上下文（Request Context）是一个保存了当前请求相关信息的容器，它可以在一个请求的生命周期内被使用，可以用来获取当前请求的相关信息，如请求的 URI、请求参数、响应信息等。

在 imi 中，请求上下文的实现是基于 Swoole 协程上下文（Coroutine Context）的，因此在 Swoole 环境下，请求上下文实际上就是协程上下文。而在非 Swoole 环境下，如 PHP-FPM，请求上下文则是一个全局上下文对象。

无论在哪个环境中，使用请求上下文都需要通过 `Imi\RequestContext` 类来获取。


## 上下文中容器操作

```php
$object = \Imi\RequestContext::getBean('XXX');
$object = \Imi\RequestContext::getServerBean('XXX');
$object = \Imi\RequestContext::newInstance('XXX');

$container = \Imi\RequestContext::getContainer();
$object = $container->get('XXX');
```

## 上下文中的数据存储

### 获取上下文对象

```php
// @var ArrayObject $cxt
$cxt = \Imi\RequestContext::getContext();
```

### 在上下文中存取值

```php
\Imi\RequestContext::get('myKey', 'default');
\Imi\RequestContext::set('myKey', '123456');
\Imi\RequestContext::unset('myKey');
```

### 批量在上下文中设置值

```php
\Imi\RequestContext::muiltiSet([
    'myKey1' => '123456',
    'myKey2' => '456789',
    'myKey3' => '123456',
]);
```

### 在闭包中获取上下文操作并返回值

```php
// 返回`123`
$result = \Imi\RequestContext::use(function (ArrayObject $cxt) {
    $cxt['myKey2'] = '789';
    unset($cxt['myKey3']);

    return '123';
});
```

### 执行一个闭包并再上下文中记住其返回值

```php
// 返回值`3`并写入上下文中存储
$result = \Imi\RequestContext::remember('myKey3', function () {
    return 1 + 2;
});
```

### 推迟执行

当协程释放时触发，先进后出

```php
use function Yurun\Swoole\Coroutine\goWait;
$result = [];
goWait(static function () use (&$result): void {
    RequestContext::defer(static function () use (&$result): void {
        $result[] = 1;
    });
    RequestContext::defer(static function () use (&$result): void {
        $result[] = 2;
    });
}, -1, true);
var_dump($result); // [2, 1]
```
