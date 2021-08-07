# imi - PHP 长连接微服务分布式开发框架

<p align="center">
    <a href="https://www.imiphp.com" target="_blank">
        <img src="https://raw.githubusercontent.com/imiphp/imi/2.x/res/logo.png" alt="imi" />
    </a>
</p>

[![Latest Version](https://img.shields.io/packagist/v/imiphp/imi.svg)](https://packagist.org/packages/imiphp/imi)
![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/imiphp/imi/ci/dev)
[![Php Version](https://img.shields.io/badge/php-%3E=7.4-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.7.0-brightgreen.svg)](https://github.com/swoole/swoole-src)
[![imi Doc](https://img.shields.io/badge/docs-passing-green.svg)](https://doc.imiphp.com)
[![imi License](https://img.shields.io/badge/license-MulanPSL%201.0-brightgreen.svg)](https://github.com/imiphp/imi/blob/master/LICENSE)
[![star](https://gitee.com/yurunsoft/IMI/badge/star.svg?theme=gvp)](https://gitee.com/yurunsoft/IMI/stargazers)

## 介绍

imi 是一款支持长连接微服务分布式的 PHP 开发框架，它可以运行在 PHP-FPM、Swoole、Workerman 多种容器环境下。

imi 拥有丰富的功能组件，v2.0 版本内置了 2 个分布式长连接服务的解决方案。

imi 框架现在已经稳定运行在：文旅电商平台、物联网充电云平台、停车云平台、支付微服务、短信微服务、钱包微服务、卡牌游戏服务端、数据迁移服务（虎扑）等项目中。

> imi 第一个版本发布于 2018 年 6 月 21 日

imi 框架交流群：17916227 [![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)

## 官方视频教程（完全免费）

imi 框架入门教程（免费11集全）<https://www.bilibili.com/video/av78158909>

imi 框架进阶教程——五子棋游戏开发(免费7集全)<https://space.bilibili.com/768718/channel/detail?cid=136926>

### 核心组件

* Http、Http2、WebSocket、TCP、UDP、MQTT 服务器
* 分布式长连接解决方案（消息队列模式、网关模式）
* MySQL 连接池 (主从+负载均衡)
* Redis 连接池 (主从+负载均衡)
* 超好用的 ORM (Db、Redis、Tree)
* 毫秒级热更新
* AOP
* Bean 容器
* 缓存 (Cache)
* 配置读写 (Config)
* 枚举 (Enum)
* 事件 (Event)
* 门面 (Facade)
* 验证器 (Validate)
* 锁 (Lock)
* 日志 (Log)
* 异步任务 (Task)

### 扩展组件

* [MQTT](src/Components/mqtt)
* [RPC](src/Components/rpc)
* [gRPC](src/Components/grpc)
* [Hprose](src/Components/hprose)
* [消息队列](src/Components/queue)
* [AMQP](src/Components/amqp) (支持 AMQP 协议的消息队列都可用，如：RabbitMQ)
* [Kafka](src/Components/kafka)
* [JWT](src/Components/jwt) (在 imi 框架中非常方便地接入 jwt)
* [权限控制](src/Components/access-control)
* [Smarty 模版引擎](src/Components/smarty)
* [限流](src/Components/rate-limit)
* [跨进程变量共享](src/Components/shared-memory)
* [雪花算法发号器](src/Components/snowflake)
* [Swagger API 文档生成](src/Components/apidoc)
* [Swoole Tracker](src/Components/swoole-tracker)

> 这些组件都已经在 imi 主仓库中维护

## 开始使用

创建 Http Server 项目：`composer create-project imiphp/project-http:2.0.x-dev`

创建 WebSocket Server 项目：`composer create-project imiphp/project-websocket:2.0.x-dev`

创建 TCP Server 项目：`composer create-project imiphp/project-tcp:2.0.x-dev`

创建 UDP Server 项目：`composer create-project imiphp/project-udp:2.0.x-dev`

创建 MQTT Server 项目：`composer create-project imiphp/project-mqtt`

[完全开发手册](https://doc.imiphp.com)

## 运行环境

* Linux 系统 (Swoole 不支持在 Windows 上运行)
* [PHP](https://php.net/) >= 7.4
* [Composer](https://getcomposer.org/) >= 2.0
* [Swoole](https://www.swoole.com/) >= 4.7.0
* Redis、PDO 扩展

## Docker

推荐使用 Swoole 官方 Docker：<https://github.com/swoole/docker-swoole>

## 成功案例

不论您使用 imi 开发的是个人项目还是公司项目，不管是开源还是商业，都可以向我们提交案例。

案例可能会被采纳并展示在 imi 官网、Swoole 官网等处，这对项目的推广和发展有着促进作用。

**提交格式：**

* 项目名称
* 项目介绍
* 项目地址（官网/下载地址/Github等至少一项）
* 联系方式（电话/邮箱/QQ/微信等至少一项）
* 项目截图（可选）
* 感言

### 案例展示

* [看个蛋影视搜索 - 全网影视资源搜索平台](http://www.kangedan.com/)

![看个蛋影视搜索](https://www.imiphp.com/images/anli/kangedan.jpg "看个蛋影视搜索")

**项目介绍：** 从最早的建站初心是为了自己方便！放到网络的以来，当流量越来越大的时候是要考虑升级配置还是重构项目，前几天 git 上看到 imiphp，索性就拿来实践一下，也是简单就重构出了所有页面，模版引擎引入了 TP 的 think-template，整个重构也就一天不到，所以 imiphp 确实很容易上手！加油！

---

* [虎扑 - 上亿数据迁移服务]

![虎扑](https://www.imiphp.com/images/anli/hupu.jpg "虎扑")

**项目介绍：** 随着数据规模的越来越大，mysql已经不能适用大数据多维度的查询，需要用ES等一类的搜索引擎，进行多维度的分词查询，MYSQL现阶段使用按天分表存储，不能满足跨天的长时间查询。

如何以最快的速度完成数据迁移，将数据库中的数据迁移到ES中，是需要评估的一个重要技术点。

在高IO密集的场景下，单次请求需要80毫秒，imi运用Swoole协程，不断在用户态和内核态之间进行切换，充分利用计算机CPU，从而能快速完成海量数据迁移。

根据普罗米修斯的监控统计，在 两台 2C 4G的机器上，imi以每秒钟同步1000~1500条的同步速度，完成了上亿级别的数据迁移。

博文地址：<https://blog.csdn.net/qq_32783703/article/details/113576741>

---

* [教书先生API - 提供免费接口调用平台](https://api.oioweb.cn/)

![教书先生API](https://www.imiphp.com/images/anli/jsxsapi.png "教书先生API")

**项目介绍：** 教书先生API是免费提供API数据接口调用服务平台 - 我们致力于为用户提供稳定、快速的免费API数据接口服务。

**感言：**

之前的话服务器配置是8H8G 30M这样的一个配置，每天日300万+的一个请求量，有一次是某个接口因一个错误时不时会导致服务器直接宕机，一个偶然的搜索看到了群主（宇润）大佬的一个imi项目，于是熬夜给程序内部请求核心代码换上了imi，正好手里面有一台1H2G 5M的服务器，拿来测试了一下，配合Redis 200万-300万+一点问题都没有的，最后还是要感谢宇润大佬的开源项目。

---

## 版权信息

imi 遵循 木兰宽松许可证(Mulan PSL v2) 开源协议发布，并提供免费使用。

## 鸣谢

感谢以下开源项目 (按字母顺序排列) 为 imi 提供强力支持！

* [doctrine/annotations](https://github.com/doctrine/annotations) (PHP 注解处理类库)
* [PHP](https://php.net/) (没有 PHP 就没有 imi)
* [Swoole](https://www.swoole.com/) (没有 Swoole 就没有 imi)

## 贡献者

[![贡献者](https://opencollective.com/IMI/contributors.svg?width=890&button=false)](https://github.com/imiphp/imi/graphs/contributors)

你想出现在贡献者列表中吗？

你可以做的事（包括但不限于以下）：

* 纠正拼写、错别字
* 完善注释
* bug修复
* 功能开发
* 文档编写
* 教程、博客分享

> 最新代码以 `dev` 分支为准，提交 `PR` 也请合并至 `dev` 分支！

提交 `Pull Request` 到本仓库，你就有机会成为 imi 的作者之一！

参与框架开发教程详见：<doc/adv/devp.md>

## 捐赠

![捐赠](https://cdn.jsdelivr.net/gh/imiphp/imi@dev/res/pay.png)

开源不求盈利，多少都是心意，生活不易，随缘随缘……
