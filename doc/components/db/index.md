# 数据库操作

[toc]

## Db 驱动对象操作

用于直接执行 SQL

```php
// 获取数据库连接实例，每个RequestContext中共用一个
$db = Db::getInstance();
// 读库
$db = Db::getInstance($poolName, QueryType::READ);
// 写库
$db = Db::getInstance($poolName, QueryType::WRITE);

// ---

// 执行 SQL 并返回受影响的行数
// public static function exec(string $sql, array $bindValues = [], ?string $poolName = null, int $queryType = QueryType::WRITE): int
$rows = Db::exec('update tb_xxx set age=111 where id=?', [123]);

// 执行 SQL 返回结果
// public static function select(string $sql, array $bindValues = [], ?string $poolName = null, int $queryType = QueryType::WRITE): ?IResult
$result = Db::select('select * from tb_xxx id=?', [123]);
var_dump($result->getArray()); // 更多用法参考文档

// 预处理
$stmt = Db::prepare('select * from tb_xxx id=?');
$stmt->execute([123]);
var_dump($stmt->fetchAll()); // 更多用法参考文档

// ---

// 获取新的数据库连接实例
$db = Db::getNewInstance();
// 读库
$db = Db::getNewInstance($poolName, QueryType::READ);
// 写库
$db = Db::getNewInstance($poolName, QueryType::WRITE);
// 释放连接，回归连接池，配合 getNewInstance() 使用
Db::release($db);

// ---

$returnValue = Db::use(function(IDb $db){
    // 操作 $db
    return 'imi';
}); // imi
```

### 方法

```php
/**
 * 打开
 */
public function open(): bool;

/**
 * 关闭.
 */
public function close(): void;

/**
 * 是否已连接.
 */
public function isConnected(): bool;

/**
 * ping 检查是否已连接.
 */
public function ping(): bool;

/**
 * 启动一个事务
 */
public function beginTransaction(): bool;

/**
 * 提交一个事务
 */
public function commit(): bool;

/**
 * 回滚事务
 * 支持设置回滚事务层数，如果不设置则为全部回滚.
 */
public function rollBack(?int $levels = null): bool;

/**
 * 获取事务层数.
 */
public function getTransactionLevels(): int;

/**
 * 返回错误码
 *
 * @return mixed
 */
public function errorCode();

/**
 * 返回错误信息.
 */
public function errorInfo(): string;

/**
 * 获取最后一条执行的SQL语句.
 */
public function lastSql(): string;

/**
 * 执行一条 SQL 语句，并返回受影响的行数.
 */
public function exec(string $sql): int;

/**
 * 批量执行 SQL，返回查询结果.
 */
public function batchExec(string $sql): array;

/**
 * 取回一个数据库连接的属性.
 *
 * @param mixed $attribute
 *
 * @return mixed
 */
public function getAttribute($attribute);

/**
 * 设置属性.
 *
 * @param mixed $attribute
 * @param mixed $value
 */
public function setAttribute($attribute, $value): bool;

/**
 * 检查是否在一个事务内.
 */
public function inTransaction(): bool;

/**
 * 返回最后插入行的ID或序列值
 */
public function lastInsertId(?string $name = null): string;

/**
 * 返回受上一个 SQL 语句影响的行数.
 */
public function rowCount(): int;

/**
 * 准备执行语句并返回一个语句对象
 */
public function prepare(string $sql, array $driverOptions = []): \Imi\Db\Interfaces\IStatement;

/**
 * 执行一条SQL语句，返回一个结果集作为PDOStatement对象
 */
public function query(string $sql): \Imi\Db\Interfaces\IStatement;

/**
 * 获取原对象实例.
 *
 * @return object
 */
public function getInstance();

/**
 * Get 事务管理.
 */
public function getTransaction(): \Imi\Db\Transaction\Transaction;

/**
 * 创建查询构建器.
 */
public function createQuery(?string $modelClass = null): \Imi\Db\Query\Interfaces\IQuery;
```

## 预处理

使用预处理可以防止 SQL 注入。

```php
$db = Db::getNewInstance();

$stmt = $db->prepare('select ?'); // 预处理问号形式
$stmt->execute([1]); // 执行只能传索引数组

$stmt = $db->prepare('select :abc'); // 预处理定义参数名
$stmt->execute([':abc' => 1]); // 执行可以带冒号传参
$stmt->execute(['abc' => 1]); // 执行可以不带冒号传参
```

## 查询构建器

imi 中数据库查询连贯操作都来自于查询器，查询器的创建方式：

```php
use Imi\Db\Db;
$query = Db::query();
$query = Db::query('mysql2'); // 指定连接池名
$query = Db::query('mysql2', XXXModel::class); // 指定模型名
$query = Db::query('mysql2', XXXModel::class, \Imi\Db\Query\QueryType::READ); // 从库
```

### 事务

获取连接顺带自动开启/提交/回滚事务：

```php
Db::transUse(function(IDb $db){

});
Db::transUse(function(IDb $db){

}, $poolName, QueryType::WRITE);
```

> `Db::transUse()` 不能在回调中使用模型

获取连接后，想要使用某个连接，执行事务操作，自动开启/提交/回滚事务：

```php
$db = Db::getInstance();
Db::trans($db, function(IDb $db){

});
```

> 可以使用模型

使用回调来使用当前上下文中的资源：

```php
Db::transContext(function(IDb $db){

});
Db::transContext(function(IDb $db){

}, $poolName, QueryType::WRITE);
```

> 可以使用模型

手动控制事务：（不推荐）

```php
// 开启事务
Db::getInstance()->beginTransaction();
// 提交事务
Db::getInstance()->commit();
// 回滚事务
Db::getInstance()->rollBack();
```

**自动事务处理：**

`@Transaction` 注解，类：`Imi\Db\Annotation\Transaction`

这个注解可以加在任意方法上，在方法调用前开启事务，在方法中抛出异常时回滚事务，方法成功返回时提交事务。

`@Transaction`

`@Transaction(autoCommit="自动提交事务true/false，默认为true")`

例：

```php
use Imi\Db\Annotation\Transaction;

/**
 * @Transaction
 */
public function create()
{
    // 你的代码，抛异常就回滚，没异常就提交
}
```

**事务类型：**

* 如果当前不在事务中则开启事务（默认）

`@Transaction(type=TransactionType::AUTO)`

* 事务嵌套

`@Transaction(type=TransactionType::NESTING)`

* 该方法必须在事务中被调用

`@Transaction(type=TransactionType::REQUIREMENT)`

**部分回滚：**

`@Transaction(rollbackType=RollbackType::PART, rollbackLevels="回滚层数，默认为1；当 $rollbackType 为 RollbackType::PART 时有效。设为null则全部回滚")`

**事务监听：**

监听提交事务：

```php
$db = Db::getInstance();
$db->getTransaction()->onTransactionCommit(function($param){
    $data = $param->getData();
    $db = $data['db'];
    $level = $data['level']; // 第几层事务，支持事务嵌套
});
```

监听回滚事务：

```php
$db = Db::getInstance();
$db->getTransaction()->onTransactionRollback(function($param){
    $data = $param->getData();
    $db = $data['db'];
    $level = $data['level']; // 第几层事务，支持事务嵌套
});
```

### 指定表 (table/from)

```php
// 指定表名
Db::query()->table('tb_test');
// 指定表名并且设置别名
Db::query()->table('tb_test', 'test');
// 指定表名和数据库名
Db::query()->table('tb_test', null, 'db1');
// 传入参数原样代入到SQL中
Db::query()->tableRaw('tb_test');

// 如果连接配置设置了表前缀：tb_
Db::query()->table('test')->select(); // select * from tb_test

// 设置表前缀
Db::query()->tablePrefix('')->table('test')->select(); // select * from test
```

> `table()` 和 `tableRaw()` 也可以使用 `from()` 和 `fromRaw()` 代替。

### 指定分区

```php
Db::query()->partition(['a', 'b']); // 传数组
Db::query()->partitionRaw('`a`,`b`'); // 传 sql
```

> 支持 MySQL 下的增删改查操作

### distinct

```php
// 结果唯一
Db::query()->distinct();
// 结果不唯一
Db::query()->distinct(false);
```

### 指定字段 (field)

```php
// 指定一个字段
Db::query()->field('id');

// 指定多个字段，也支持设置别名。空格和as都支持
Db::query()->field('id', 'name1 name', 'age1 as age');

// 和上面的用法等同
Db::query()->field(['id', 'name1 name', 'age1 as age']);

// 传入参数原样代入到SQL中
Db::query()->fieldRaw('id, name1 name, age1 as age');

// 传入参数原样代入到SQL中，也支持参数绑定（不支持同时使用 ? 和 :xxx）
Db::query()->fieldRaw('id, name1 name, age1 as age, ? as value', null, [123]);
Db::query()->fieldRaw('id, name1 name, age1 as age, :value as value', null, [':value' => 123]);
```

### 条件 where

#### where

```php
// id = 1
Db::query()->where('id', '=', 1);

// id > 1
Db::query()->where('id', '>', 1);

// 使用 Raw 原样代入值，例：value = 1 + 2
Db::query()->where('value', '=', new \Imi\Db\Query\Raw('1 + 2'));

// title like '%test%'
Db::query()->where('title', 'like', '%test%');

// id between 1 and 10
Db::query()->where('id', 'between', [1, 10]);

// id not between 1 and 10
Db::query()->where('id', 'not between', [1, 10]);

// or id = 1
Db::query()->where('id', '=', 1, 'or');
Db::query()->orWhere('id', '=', 1);

// JSON 类型字段条件
Db::query()->where('field1->uid', '=', 1);
```

#### TP 风格 where

```php
// select * from `tb_test` where (`id` = 1 or (`id` = 2 ) and `title` like '%test%' and `age` > 18 and (`id` = 2 or (`id` = 3 ) ) )
Db::query()->from('tb_test')->whereEx([
    'id'    =>  1,
    'or'    =>  [
        'id'    =>  2,
    ],
    'title' =>  ['like', '%test%'],
    'age'   =>  ['>', 18],
    'and'   =>  [
        'id'    =>  2,
        'or'    =>  [
            'id'    =>  3,
        ]
    ]
]);
```

#### whereRaw

```php
// 传入参数原样代入到SQL中
Db::query()->whereRaw('id >= 1');
// 传入参数原样代入到SQL中，并且为or条件
Db::query()->whereRaw('id >= 1', 'or');
Db::query()->orWhereRaw('id >= 1');

// 支持参数绑定（不支持同时使用 ? 和 :xxx）
// 传入参数原样代入到SQL中，并且为or条件
Db::query()->whereRaw('id >= ?', 'or', [1]);
Db::query()->orWhereRaw('id >= ?', [1]);
Db::query()->whereRaw('id >= :value', 'or', [':value' => 1]);
Db::query()->orWhereRaw('id >= :value', [':value' => 1]);
```

#### whereBrackets

```php
// 查询条件收集器：where (age < 14 or age > 60)
Db::query()->where('id', '=', 1)->whereBrackets(function(\Imi\Db\Query\Interfaces\IQuery $query, \Imi\Db\Query\Interfaces\IWhereCollector $where) {
    // 注意：使用第 2 个参数 $where，而不是 $query
    $where->where('age', '<', 14)->orWhere('age', '>', 60);
    // 不要有返回值
}, 'or');

// where id = 1 or (age < 14)
Db::query()->where('id', '=', 1)->whereBrackets(function(\Imi\Db\Query\Interfaces\IQuery $query) {
    // 返回条件字符串
    return 'age < 14';
}, 'or');

// 支持使用 sql 语句: where id = 1 or (age > 10 and age < 14)
Db::query()->where('id', '=', 1)->whereBrackets(function(\Imi\Db\Query\Interfaces\IQuery $query) {
    // 返回 Where 系列数组
    return [
        \Imi\Db\Query\Where\Where::raw('age > 10'),
        new \Imi\Db\Query\Where\Where('age', '<', 14),
    ];
}, 'or');

// where id = 1 or (age < 14)
Db::query()->where('id', '=', 1)->whereBrackets(function(\Imi\Db\Query\Interfaces\IQuery $query) {
    // 返回 Where 系列对象
    return new \Imi\Db\Query\Where\Where('age', '<', 14);
}, 'or');

// OR 条件
Db::query()->where('id', '=', 1)->orWhereBrackets(function(\Imi\Db\Query\Interfaces\IQuery $query) {
    // 返回 Where 系列对象
    return new \Imi\Db\Query\Where\Where('age', '<', 14);
});
```

#### whereStruct

```php
// or age < 14
Db::query()->whereStruct(new \Imi\Db\Query\Where\Where('age', '<', 14), 'or');
Db::query()->orWhereStruct(new \Imi\Db\Query\Where\Where('age', '<', 14));
```

#### postWhere

用法类似 `whereBrackets`，后置 Where 条件

```php
// where `a` = 1 and (`b` = 2 or `c` = 3) and (`d` != 4)
Db::query()->postWhere(static function (\Imi\Db\Query\Interfaces\IQuery $query, \Imi\Db\Query\Interfaces\IWhereCollector $where) {
                $whereCollector->where('b', '=', 2)
                                ->orWhere('c', '=', 3);
            })
            ->postWhere(static fn () => new Where('d', '!=', 4, 'or'))
            ->where('a', '=', 1);
```

#### 全文搜索（fullText）

imi 查询构建器提供的全文搜索功能，不仅支持全文搜索匹配，还支持返回匹配分数和排序等功能。

**支持的数据库：**

* MySQL
* PostgreSQL

**基础写法：**

```php
// 最简单用法
$query = Db::query()->fullText('字段名', '搜索词');

// 支持多个字段搜索
$query = Db::query()->fullText(['字段A', '字段B'], '搜索词');
```

**MySQL：**

```php
use \Imi\Db\Mysql\Query\FullText\MysqlFullTextOptions;

// 以下所有 set 语句都是可选，根据实际情况选择是否设置
$options = (new MysqlFullTextOptions())->setMinScore(0) // 匹配度最小值，取值范围是0-1之间的小数，默认值0
                                       ->setWhereLogicalOperator('and') // where条件逻辑运算符，默认值and
                                       ->setScoreFieldName('score') // 匹配分数字段名，默认值 null 不返回匹配分数
                                       ->setOrderDirection('desc') // 排序方向，默认值 null 不排序
                                       ->setSearchModifier(\Imi\Db\Mysql\Query\FullText\SearchModifier::IN_NATURAL_LANGUAGE_MODE) // 搜索修饰符，默认值空字符串。可使用 \Imi\Db\Mysql\Query\FullText\SearchModifier 中定义的常量
                                       ;
$query = Db::query()->fullText('字段名', '搜索词', $options);
```

> `setSearchModifier()` 具体参考：<https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html>

**PostgreSQL：**

```php
use \Imi\Pgsql\Db\Query\FullText\PgsqlFullTextOptions;

// 以下所有 set 语句都是可选，根据实际情况选择是否设置
$options = (new PgsqlFullTextOptions())->setMinScore(0) // 匹配度最小值，取值范围是0-1之间的小数，默认值0
                                       ->setWhereLogicalOperator('and') // where条件逻辑运算符，默认值and
                                       ->setScoreFieldName('score') // 匹配分数字段名，默认值 null 不返回匹配分数
                                       ->setOrderDirection('desc') // 排序方向，默认值 null 不排序
                                       ->setLanguage('simple') // 分词语言，默认值 null
                                       ->setTsQueryFunction(\Imi\Pgsql\Db\Query\FullText\TsQuery::TO_TSQUERY) // ts_query 函数，默认值 plainto_tsquery
                                       ->setTsRankFunction(\Imi\Pgsql\Db\Query\FullText\TsRank::TS_RANK) // ts_rank 函数，默认值 ts_rank_cd
```

> `setLanguage()`、`setTsQueryFunction()` 和 `setTsRankFunction()` 具体参考：<https://www.postgresql.org/docs/15/textsearch-controls.html>

#### 其它

```php
// or age between 1 and 14
Db::query()->whereBetween('age', 1, 14, 'or');
Db::query()->orWhereBetween('age', 1, 14);

// or age not between 1 and 14
Db::query()->whereNotBetween('age', 1, 14, 'or');
Db::query()->orWhereNotBetween('age', 1, 14);

// or age in (1,2,3)
Db::query()->whereIn('age', [1, 2, 3], 'or');
Db::query()->orWhereIn('age', [1, 2, 3]);

// or age not in (1,2,3)
Db::query()->whereNotIn('age', [1, 2, 3], 'or');
Db::query()->orWhereNotIn('age', [1, 2, 3]);

// or age is null
Db::query()->whereIsNull('age', 'or');
Db::query()->orWhereIsNull('age');

// or age is not null
Db::query()->whereIsNotNull('age', 'or');
Db::query()->orWhereIsNotNull('age');
```

#### JSON 字段支持

看如下代码，`data`为`json`类型字段，查询`json`对象中的`member.age`为`11`的记录

```php
Db::query()->where('data->member.age', '=', 11)->select();
```

### join

```php
// select * from tb_test1 left join tb_test2 on tb_test1.aid = tb_test2.bid
Db::query()->table('tb_test1')->join('tb_test2', 'tb_test1.aid', '=', 'tb_test2.bid');
// select * from tb_test1 left join tb_test2 as test2 on tb_test1.aid = test2.bid
Db::query()->table('tb_test1')->join('tb_test2', 'tb_test1.aid', '=', 'test2.bid', 'test');
// select * from tb_test1 right join tb_test2 on tb_test1.aid = tb_test2.bid and tb_test1.age > 14
Db::query()->table('tb_test1')->join('tb_test2', 'tb_test1.aid', '=', 'tb_test2.bid', null, new \Imi\Db\Query\Where\Where('tb_test1.age', '>', '14'), 'right');

// select * from tb_test1 left join tb_test2 on tb_test1.aid = tb_test2.bid
Db::query()->table('tb_test1')->joinRaw('left join tb_test2 on tb_test1.aid = tb_test2.bid');

// 支持参数绑定（不支持同时使用 ? 和 :xxx）
// select * from tb_test1 left join tb_test2 on tb_test1.aid = tb_test2.bid and tb_test2.xxx = ?
Db::query()->table('tb_test1')->joinRaw('left join tb_test2 on tb_test1.aid = tb_test2.bid and tb_test2.xxx = ?', [123]);
// select * from tb_test1 left join tb_test2 on tb_test1.aid = tb_test2.bid and tb_test2.xxx = :value
Db::query()->table('tb_test1')->joinRaw('left join tb_test2 on tb_test1.aid = tb_test2.bid and tb_test2.xxx = :value', [':value' => 123]);

// 下面三种用法，第5个参数都支持传Where
// left join
Db::query()->table('tb_test1')->leftJoin('tb_test2', 'tb_test1.aid', '=', 'tb_test2.bid');
// right join
Db::query()->table('tb_test1')->rightJoin('tb_test2', 'tb_test1.aid', '=', 'tb_test2.bid');
// cross join
Db::query()->table('tb_test1')->crossJoin('tb_test2', 'tb_test1.aid', '=', 'tb_test2.bid');
```

### order

```php
// order by id asc, age desc
Db::query()->order('id')->order('age', 'desc');

// order by id desc
Db::query()->orderRaw('id desc');

// order by id desc, 1 asc
Db::query()->orderRaw('id desc, ? asc', [1]);

// order by id desc, 1 asc
Db::query()->orderRaw('id desc, :value asc', [':value' => 1]);

// JSON 类型参数排序
Db::query()->order('field1->uid', 'desc');
```

### group by

```php
// group by id, name
Db::query()->group('id', 'name');

// group by sum(id)
Db::query()->groupRaw('sum(id)');

// group by sum(id), ?
Db::query()->groupRaw('sum(id), ?', [123]);

// group by sum(id), :value
Db::query()->groupRaw('sum(id), :value', [':value' => 123]);
```

### having

`having()`用法同`where()`

`havingRaw()`用法同`whereRaw()`

`havingBrackets()`用法同`whereBrackets()`

`havingStruct()`用法同`whereStruct()`

### 分页查询

```php
// limit 900, 100
Db::query()->page(10, 100);

// limit 10, 1
Db::query()->offset(10)->limit(1);

// limit 1
Db::query()->limit(1);
```

### 分页查询带扩展字段

**查询总记录数、总页数：**

```php
$page = 1;
$count = 10;
$data = Db::query()->from('xxxtable')->paginate($page, $count);
// 指定转数组后的字段名
$data = TDb::query()->from('xxxtable')->paginate($page, $count, [
    'field_list' => 'list',
    'field_limit' => 'limit',
    'field_total' => 'total',
    'field_page_count' => 'page_count',
]);

$data->getList(); // 列表数据
$data->getTotal(); // 总记录数
$data->getLimit(); // $count === 10
$data->getPageCount(); // 总页数

var_dump($data->toArray()); // 转数组
var_dump(json_encode($data)); // 支持序列化
// 数据格式如下：
[
    'list'          => [],
    'total'         => 100,
    'limit'         => 10,
    'page_count'    => 10,
]
```

**不查询总记录数、总页数：**

```php
$page = 1;
$count = 10;
$data = Db::query()->from('xxxtable')->paginate($page, $count, [
    'total' =>  false,
]);
var_dump($data->toArray()); // 转数组
var_dump(json_encode($data)); // 支持序列化
// 数据格式如下：
[
    'list'          => [],
    'limit'         => 10,
]
```

**全局设置转数组后的字段名：**

配置`@app.db.paginate.fields`:

```php
[
    'list' => 'list',
    'limit' => 'limit',
    'total' => 'total',
    'pageCount' => 'page_count',
]
```

**指定查询记录数量时的字段：**

一般用于带 `distinct` 的查询，例如：

```php
$page = 1;
$count = 10;
$data = Db::query()->from('xxxtable')->distinct()->field('xxxtable.*')->paginate($page, $count, [
    'countField' => 'distinct xxxtable.id',
]);
```

### 高性能分页查询

**原理：**

* 先查出记录 id
* 再根据 id 查询记录

**注意：**

* 仅支持 MySQL 数据库
* 不支持 DISTINCT 查询

```php
// 首先准备好查询构建器
$query = Db::query()->from('xxxtable');

// 实例化大表分页类，字段名默认 id
$pagination = new \Imi\Db\Mysql\Query\Pagination\BigTablePagination($query);
// 指定主表主键字段名，如果涉及多张表关联，这里需要指定主表的主键字段名
$pagination = new \Imi\Db\Mysql\Query\Pagination\BigTablePagination($query, 'xxxtable.id');
// 第三个参数表示实际查询记录时，是否禁止清空 where 条件，不传则为 true，一般不需要传 false
$pagination = new \Imi\Db\Mysql\Query\Pagination\BigTablePagination($query, 'xxxtable.id', false);

// 只查列表，返回值同 $query->select()，Result 对象
$result = $pagination->select();
// 获取数组
$result->getArray();

// 分页查询，返回值同 $query->paginate()，PaginationResult 对象
$page = 1;
$limit = 10;
$result = $pagination->paginate($page, $limit);
```

## 查询执行

### 查询记录

#### 查询单行

```php
$result = Db::query()->table('tb_test')->select();
$result->get(); // 数组
$result->get($className); // $className对应的类对象
```

#### 查询多行

```php
$result = Db::query()->table('tb_test')->select();
$result->getArray(); // 数组内嵌套数组
$result->getArray($className); // 数组内嵌套$className对应的类对象
$result->getRowCount(); // 获取查询出的记录行数
```

#### 查询一行

```php
Db::query()->table('tb_test')->find();

Db::query()->table('tb_test')->find($className); // $className 默认null，可以不填，用途参考数据集 get 方法
```

#### 查询单个值

```php
Db::query()->table('tb_test')->value('username');
Db::query()->table('tb_test')->value('username', -1); // 当数据查询不到时返回的默认值 -1
```

#### 查询指定列

```php
// 查询 username 列并作为数组返回
Db::query()->table('tb_test')->column('username');
// 结果值如下：
[
    'username1',
    'username2',
    'username4',
]

// 查询 username 列并返回 id 做下标，username 作为值的数组
Db::query()->table('tb_test')->column('username', 'id');
// 结果值如下：
[
    1 => 'username1',
    2 => 'username2',
    3 => 'username4',
]

// 查询 username、password 列并返回 id 做下标的数组
Db::query()->table('tb_test')->column(['username', 'password'], 'id');
// 结果值如下：
[
    1 => ['username' => 'username1', 'password' => '123'],
    2 => ['username' => 'username2', 'password' => '456'],
    3 => ['username' => 'username3', 'password' => '789'],
]
```

#### 构建查询语句

构建语句，但不执行

```php
$query = Db::query()->table('tb_test');
$sql = $query->buildSelectSql(); // 构建 SQL
$binds = $query->getBinds(); // 获取预处理绑定的值
```

> 注意不要重复构建，同一个对象在执行 `execute()` 前只能构建一次

#### 分块查询

##### chunkById

利用有序字段进行分段读取，返回符合条件的数据，对于大型数据集结果，可以有效缓解数据库压力，降低应用内存消耗，提升稳定性。

| 参数      | 类型 | 说明                                                        |
|---------| ------ |-----------------------------------------------------------|
| limit   | int    | 每次查询的块大小                                                  |
| column  | string | 用于分块的有序字段，建议是有序且存在索引的数值字段，一般情况下可以利用主键                     |
| alias   | string | 用于分块的有序字段的别名，一般情况下跟`column`是一致的，无须设置，如果定义字段查询结果的别名时可设置该参数 |
| orderBy | string | 用于分块的有序字段排序方式指定，支持`asc`、`desc`，默认为`asc`                   |

> 用于查询的字段的值必须是唯一且递增的。
> 该查询方法是不支持用户设置排序的，任何类型的排序都将被重置，仅能通过`column`指定一个字段的排序方式。
> 对于`ORM`下使用，由于不是一次性载入全部数据，预加载功能对于每个块都是重复加载的，建议根据实际情况决定是否实现一个缓存查询来替代预加载。

```php
// 按 10 条每块遍历全部符合条件的行。

foreach (Db::query()->table('tb_test')->chunkById(10, 'id') as $result)
{
    $list = $result->getArray(); // select 结果集
    // 遍历结果集
    foreach ($list as $row)
    {
        var_dump($row);
    }
}

// 还有一个更简单的用法

foreach (Db::query()->table('tb_test')->chunkById(10, 'id')->each() as $row)
{
    var_dump($row);
}
```

##### chunkByOffset

利用`limit`进行查询驱动分块，效率与一般分页查询没区别，相对`chunkById`兼容更多的场景，如果追求性能还是推荐`chunkById`。

| 参数 | 类型 | 说明                                                        |
| ------ | ------ |-----------------------------------------------------------|
| limit  | int    | 每次查询的块大小                                                  |

> 对于`ORM`下使用，由于不是一次性载入全部数据，预加载功能对于每个块都是重复加载的，建议根据实际情况决定是否实现一个缓存查询来替代预加载。

```php
// 按 10 条每块遍历全部符合条件的行。

foreach (Db::query()->table('tb_test')->chunkByOffset(10) as $result)
{
    $list = $result->getArray(); // select 结果集
    // 遍历结果集
    foreach ($list as $row)
    {
        var_dump($row);
    }
}

// 还有一个更简单的用法

foreach (Db::query()->table('tb_test')->chunkByOffset(10)->each() as $row)
{
    var_dump($row);
}
```

##### chunkEach

> 该方法已弃用并计划`3.0`移除，请使用`chunkById()->each()`or`chunkByOffset()->each()`替代。

#### 游标查询

游标查询能对于查询大结果集时能有效节约应用内存消耗，对于数据库的消耗与`select`无差别。
对于`ORM`下使用，由于不是一次性载入全部数据，与预加载功能不兼容，不推荐对于游标查询进行任何的模型关联操作。

```php
foreach (Db::query()->table('tb_test')->cursor() as $data)
{
    var_dump($data); // 输出单条查询结果
}
```

#### 聚合函数

```php
// count(*)
Db::query()->table('tb_test')->count();

// count(id)
Db::query()->table('tb_test')->count('id');

// sum(id)
Db::query()->table('tb_test')->sum('id');

// avg(id)
Db::query()->table('tb_test')->avg('id');

// max(id)
Db::query()->table('tb_test')->max('id');

// min(id)
Db::query()->table('tb_test')->min('id');

// 其它自定义：test(id)
Db::query()->table('tb_test')->aggregate('test', 'id');
```

### 插入记录

```php
// insert into tb_test(name, age) values('yurun', 666)
$result = Db::query()->table('tb_test')->insert([
    'name'  =>	'yurun',
    'age'	=>	666,
]);

// insert into tb_test values('yurun', 666)
$result = Db::query()->table('tb_test')->insert([
    'yurun',666,
]);

// 忽略已存在记录，仅支持 MySQL
// insert ignore into tb_test values('yurun', 666)
$query = Db::query();
$query->getOption()->options['ignore'] = true;
$result = $query->table('tb_test')->insert([
    'yurun',666,
]);

$result->isSuccess(); // SQL是否执行成功
$result->getLastInsertId(); // 获取最后插入的ID
$result->getAffectedRows(); // 获取影响行数
```

#### 构建插入语句

构建语句，但不执行

```php
$query = Db::query()->table('tb_test');
$sql = $query->buildInsertSql([
    'name'  =>	'yurun',
    'age'	=>	666,
]); // 构建 SQL
$binds = $query->getBinds(); // 获取预处理绑定的值
```

> 注意不要重复构建，同一个对象在执行 `execute()` 前只能构建一次

### 批量插入

```php
$result = Db::query()->from('test')->batchInsert([
    ['name'=>'a'],
    ['name'=>'b'],
    ['name'=>'c'],
]);

// 忽略已存在记录，仅支持 MySQL
$query = Db::query();
$query->getOption()->options['ignore'] = true;
$result = $query->from('test')->batchInsert([
    ['name'=>'a'],
    ['name'=>'b'],
    ['name'=>'c'],
]);

$result->isSuccess(); // SQL是否执行成功
$result->getAffectedRows(); // 获取影响行数
```

#### 构建批量插入语句

构建语句，但不执行

```php
$query = Db::query()->table('tb_test');
$sql = $query->buildBatchInsertSql([
    ['name'=>'a'],
    ['name'=>'b'],
    ['name'=>'c'],
]); // 构建 SQL
$binds = $query->getBinds(); // 获取预处理绑定的值
```

> 注意不要重复构建，同一个对象在执行 `execute()` 前只能构建一次

### 更新记录

```php
// update tb_test set name = 'yurun', age = 666 where id = 1
$result = Db::query()->table('tb_test')->where('id', '=', 1)->update([
    'name'	=>	'yurun',
    'age'	=>	666,
    // JSON 类型参数
    'field1->name'        => 'bbb', // 修改 name
    'field1->list2'       => [1, 2, 3], // 修改 list2，支持数组、对象
    'field1->list1[0].id' => '2', // 支持对数组中指定成员、对象属性赋值，支持无限级
]);

// $result使用方法同上
```

#### 构建更新语句

构建语句，但不执行

```php
$query = Db::query()->table('tb_test')->where('id', '=', 1);
$sql = $query->buildUpdateSql([
    'name'	=>	'yurun',
    'age'	=>	666,
    // JSON 类型参数
    'field1->name'        => 'bbb', // 修改 name
    'field1->list2'       => [1, 2, 3], // 修改 list2，支持数组、对象
    'field1->list1[0].id' => '2', // 支持对数组中指定成员、对象属性赋值，支持无限级
]); // 构建 SQL
$binds = $query->getBinds(); // 获取预处理绑定的值
```

> 注意不要重复构建，同一个对象在执行 `execute()` 前只能构建一次

### 替换数据

```php
// replace into tb_test set id = 1, name = 'yurun', age = 666
$result = Db::query()->table('tb_test')->replace([
    'id'	=>	1,
    'name'	=>	'yurun',
    'age'	=>	666,
]);
```

#### 构建替换语句

构建语句，但不执行

```php
$query = Db::query()->table('tb_test');
$sql = $query->buildReplaceSql([
    'id'	=>	1,
    'name'	=>	'yurun',
    'age'	=>	666,
]); // 构建 SQL
$binds = $query->getBinds(); // 获取预处理绑定的值
```

> 注意不要重复构建，同一个对象在执行 `execute()` 前只能构建一次

### 递增/递减

```php
// score 递增 1
$result = Db::query()->table('tb_test')
                     ->where('id', '=', 1)
                     ->setFieldInc('score')
                     ->update();

// score 递增 10
$result = Db::query()->table('tb_test')
                     ->where('id', '=', 1)
                     ->setFieldInc('score', 10)
                     ->update();

// score 递减 1
$result = Db::query()->table('tb_test')
                     ->where('id', '=', 1)
                     ->setFieldDec('score')
                     ->update();

// score 递减 10
$result = Db::query()->table('tb_test')
                     ->where('id', '=', 1)
                     ->setFieldDec('score', 10)
                     ->update();
```

### update/insert/replace时使用表达式

```php
// update tb_test set score = score + 1 where id = 1
$result = Db::query()->table('tb_test')
                     ->where('id', '=', 1)
                     ->setFieldExp('score', 'score + ?', [1])
                     ->update();
```

### 删除记录

```php
// delete from tb_test where id = 1
$result = Db::query()->table('tb_test')->where('id', '=', 1)->delete();

// $result使用方法同上
```

#### 构建删除语句

构建语句，但不执行

```php
$query = Db::query()->table('tb_test')->where('id', '=', 1);
$sql = $query->buildDeleteSql(); // 构建 SQL
$binds = $query->getBinds(); // 获取预处理绑定的值
```

> 注意不要重复构建，同一个对象在执行 `execute()` 前只能构建一次

### 加锁

排它锁：

```php
use Imi\Db\Mysql\Query\Lock\MysqlLock;
Db::query()->from('tb_xxx')->where('id', '=', 1)->lock(MysqlLock::FOR_UPDATE)->select()->get();
```

共享锁：

```php
use Imi\Db\Mysql\Query\Lock\MysqlLock;
Db::query()->from('tb_xxx')->where('id', '=', 1)->lock(MysqlLock::SHARED)->select()->get();
```

## 直接执行SQL

```php
$result = Db::query()->execute('select * from tb_test'));

// $result使用方法同上
```

## 参数绑定

复杂的查询时，难免需要拼接 SQL，这时候就需要参数绑定来防止注入了！

```php
// where id = 123
$result = Db::query()->whereRaw('id = ?')->bindValue(1, 123);
$result = Db::query()->whereRaw('id = :val')->bindValue(':val', 123);

// 批量绑定
$result = Db::query()->bindValues([
    ':name'	=>	'yurun',
    ':age'	=>	666,
])->execute('select * from tb_test where name = :name and age = :age');
```

## 设置/获取 Result 结果集类名

```php
$query = Db::query();
// 获取
var_dump($query->getResultClass());

// 设置
$query->setResultClass(\Imi\Db\Query\Result::class);
```

## Result 用法

```php
$result = Db::query()->table('tb_test')->select();
```

### 是否执行成功

```php
$success = $result->isSuccess(); // true/false
```

### 获取最后插入的ID

用于获取新增记录的自增字段值

```php
$lastInsertId = $result->getLastInsertId(); // int
```

### 获取影响行数

```php
$rows = $result->getAffectedRows();
```

> update 时，如果没有值被改变，可能会返回0

### 返回一行数据

#### 返回数组

```php
$dataArray = $result->get();
```

#### 返回对象

实例化这个类时，把数组传入构造方法

```php
$dataArray = $result->get(XXXModel::class);
```

### 返回数组列表

#### 成员为数组

```php
$list = $result->getArray();
```

#### 成员为对象

```php
$list = $result->getArray(XXXModel::class);
```

### 获取一列

```php
$ids = Db::query()->field('id')->select()->getColumn();
// 结果格式：[1, 2, 3]
```

### 获取标量结果

```php
$name = Db::query()->field('name')->where('id', '=', 1)->select()->getScalar();
```

### 获取记录行数

得到取回多少条记录

```php
$rowCount = $result->getRowCount();
```

### 获取执行的SQL

```php
$sql = $result->getSql();
```

### 获取结果集

```php
$statement = $result->getStatement(); // \Imi\Db\Interfaces\IStatement
```

## 渲染预编译SQL语句

主要用于日志或者调试

```php
$prepare = "select * from `test1` where `id` = :p1 and `text` = :p2 and `a1` in (:p3,:p4,:p5) and `a2` in (0 = 1)";
$bindValues = [
    ':p1' => -1,
    ':p2' => 'abc123',
    ':p3' => 1,
    ':p4' => 2,
    ':p5' => 3,
];

echo Db::debugSql($prepare, $bindValues);
// 输出
// select * from `test1` where `id` = -1 and `text` = 'abc123' and `a1` in (1,2,3) and `a2` in (0 = 1)

$prepare = "select * from `test1` where `id` = ? and `text` = ? and `a1` in (?,?) ??";
$bindValues = [-1, 'abc123', 1, 2];

echo Db::debugSql($prepare, $bindValues);
// 输出
// select * from `test1` where `id` = -1 and `text` = 'abc123' and `a1` in (1,2) ??
```
