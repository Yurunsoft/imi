# 方法参数过滤器

[toc]

imi 框架的方法参数过滤器是一种方便开发者在框架层面对方法参数进行过滤的机制。

## 注解说明

### FilterArg

| 属性名称 | 说明 |
| ------------ | ------------ 
| name | 参数名 |
| filter | 过滤器callable |

## 用法示例

简单使用：

```php
#[
    FilterArg(name: 'data', filter: 'json_decode')
]
public function test($data)
{
    var_dump($data); // 这是一个stdClass对象
}

$obj->test('{"id":1, "message": "imi nb!"}');
```

复杂用法：

结合`Callback`、`Inject`注解使用，支持使用`bean`中的方法。

```php
#[Bean(name: 'XXX')]
class TestXXX
{
    public function decode($data)
    {
        return json_decode($data, true);
    }
}

#[FilterArg(name: 'data', filter: new Callback(class: new Inject(name: 'XXX', method: 'decode')))]
public function test($data)
{
    var_dump($data); // 这是一个数组
}

$obj->test('{"id":1, "message": "imi nb!"}');
```
