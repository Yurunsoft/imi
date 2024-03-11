# Summary

[toc]

## 序言

* [项目介绍](README.md)
* [开发者社区](base/community.md)
* [技术支持](base/support.md)
* [赞助开发](base/donate.md)
* [版本支持计划](base/version/support.md)
* [参与框架开发](adv/devp.md)

## 基础入门

* [开始一个新项目](base/new.md)
* [应用配置](base/config.md)
* [常见问题](base/qa.md)
* [v2.1-v3.0升级指南](base/version/2.1-3.0.md)

## 框架核心

* [生命周期](core/lifeCycle.md)
* [容器](core/container.md)
* [请求上下文](core/requestContext.md)
* [Partial](core/partial.md)
* [事件](components/event/index.md)
  * [事件监听](components/event/index.md)
  * [事件列表](core/events.md)
* [中间件](core/middleware.md)
* [全局异常处理](core/handleError.md)
* [内部进程间通讯](core/processCommunication.md)
* [Server 对象](core/server.md)
* [长连接分布式解决方案](core/long-connection-distributed.md)
* [环境变量列表](core/env.md)
* [内置常量列表](core/consts.md)
* [单文件运行 imi（快速启动）](core/quickStart.md)

## 注解

* [注入值注解](annotations/injectValue.md)
* [方法参数过滤器](annotations/filterArg.md)
* [编写自定义注解](annotations/annotation.md)
* [注解管理器](annotations/annotationManager.md)
* [注解相关问题](annotations/qa.md)

## 多容器

* [PHP-FPM](container/php-fpm.md)
  * [服务器配置](container/php-fpm/serverConfig.md)
* [Swoole](container/swoole.md)
  * [Swoole 环境安装教程](base/env.md)
  * [子服务器（单项目多端口多协议）](core/subServer.md)
  * [单端口支持 WebSocket+Http](components/swoole/ws_and_http.md)
  * [热更新](components/hotupdate/index.md)
  * [进程名称管理](core/processNameManager.md)
  * [工具类](utils/Coroutine.md)
    * [Coroutine](utils/Coroutine.md)
    * [Swoole](utils/Swoole.md)
    * [ChannelContainer](utils/ChannelContainer.md)
  * [命令行工具](dev/process-swoole.md)
    * [进程工具](dev/process-swoole.md)
  * [事件列表](container/swoole/events.md)
* [Workerman](container/workerman.md)
  * [服务器配置](container/workerman/serverConfig.md)
  * [热更新](components/hotupdate/index.md)
  * [命令行工具](dev/process-workerman.md)
    * [进程工具](dev/process-workerman.md)
  * [事件列表](container/workerman/events.md)
* [RoadRunner](container/roadrunner.md)
  * [服务器配置](container/roadrunner/serverConfig.md)

## Http 服务

* [路由](components/httpserver/route.md)
* [控制器](components/httpserver/controller.md)
* [请求（Request）](components/httpserver/request.md)
  * [请求类](components/httpserver/request.md)
  * [AOP 拦截请求](components/httpserver/aop.md)
  * [中间件](components/httpserver/middleware.md)
* [响应（Response）](components/httpserver/view.md)
  * [响应类](components/httpserver/response.md)
  * [视图](components/httpserver/view.md)
  * [SSE](components/httpserver/sse.md)
* [RESTful](components/httpserver/restful.md)
* [Session](components/httpserver/session.md)
* [JWT](components/httpserver/jwt.md)
* [错误异常处理](components/httpserver/error.md)
* [404处理](components/httpserver/404.md)
* [HTTP 验证器](components/httpserver/validation.md)
* [超全局变量](components/httpserver/superGlobals.md)
* [请求最大执行时间](components/httpserver/maxExecuteTime.md)
* [Http2](components/httpserver/http2.md)
* [跨域和 OPTIONS 请求](components/httpserver/cros.md)
* [HTTPS 配置](components/server/ssl.md)
* [Swagger API 文档生成](dev/generate/swagger.md)

## WebSocket 服务

* [错误异常处理](components/websocketServer/error.md)
* [数据处理器](components/server/dataParser.md)
* [HTTP 路由](components/websocketServer/httpRoute.md)
* [HTTP 控制器](components/websocketServer/httpController.md)
* [WebSocket 路由](components/websocketServer/route.md)
* [WebSocket 控制器](components/websocketServer/websocketController.md)
* [中间件](components/websocketServer/middleware.md)
* [连接上下文](components/websocketServer/session.md)
* [连接分组](components/websocketServer/group.md)
* [断线重连](components/websocketServer/reconnect.md)
* [不使用中间件](components/websocketServer/noMiddleware.md)
* [WSS 配置](components/server/ssl.md)
* [向客户端推送数据（服务器工具类）](utils/Server.md)
* [WebSocket 客户端](components/websocketServer/client.md)

## TCP 服务

* [错误异常处理](components/tcpServer/error.md)
* [数据处理器](components/server/dataParser.md)
* [路由](components/tcpServer/route.md)
* [TCP 控制器](components/tcpServer/controller.md)
* [中间件](components/tcpServer/middleware.md)
* [连接上下文](components/websocketServer/session.md)
* [连接分组](components/websocketServer/group.md)
* [断线重连](components/websocketServer/reconnect.md)
* [不使用中间件](components/tcpServer/noMiddleware.md)
* [向客户端推送数据（服务器工具类）](utils/Server.md)

## UDP 服务

* [错误异常处理](components/udpServer/error.md)
* [数据处理器](components/server/dataParser.md)
* [路由](components/udpServer/route.md)
* [UDP 控制器](components/udpServer/controller.md)
* [中间件](components/udpServer/middleware.md)
* [不使用中间件](components/udpServer/noMiddleware.md)

## MQTT

* [MQTT 服务端](components/mqtt/server.md)
* [MQTT 客户端](components/mqtt/client.md)

## RPC 服务

* [gRPC](components/rpc/grpc.md)
  * [gRPC 服务开发](components/rpc/grpc.md)
  * [Protobuf](components/rpc/grpc-protobuf.md)
  * [gRPC 的 HTTP 代理网关](components/rpc/grpc-proxy.md)

## 功能组件

* [配置](components/config/index.md)
  * [配置读写](components/config/index.md)
  * [配置中心](components/config/center.md)
* [连接中心](components/connectionCenter/index.md)
* [连接池](components/pool/index.md)
* [关系型数据库 (Db)](components/db/index.md)
  * [数据库驱动](components/db/index.md)
    * [MySQL](components/db/mysql.md)
    * [PostgreSQL](components/db/pgsql.md)
  * [数据库配置](components/db/config.md)
  * [数据库操作](components/db/index.md)
  * [SQL 监听](components/db/sqlListener.md)
* [ORM](components/orm/index.md)
  * [使用说明](components/orm/index.md)
  * [数据库表模型](components/orm/RDModel.md)
    * [模型配置](components/orm/config.md)
    * [模型定义](components/orm/RDModel/definition.md)
    * [模型生成](dev/generate/model.md)
    * [模型用法](components/orm/RDModel.md)
    * [从模型生成表](dev/generate/table.md)
    * [模型事件](components/orm/RDModel/event.md)
    * [树形表模型](components/orm/TreeModel.md)
    * [模型软删除](components/orm/softDelete.md)
  * [模型关联](components/orm/relation/index.md)
    * [使用说明](components/orm/relation/index.md)
    * [一对一关联](components/orm/relation/oneToOne.md)
    * [一对多关联](components/orm/relation/oneToMany.md)
    * [多对多关联](components/orm/relation/manyToMany.md)
    * [多态一对一关联](components/orm/relation/polymorphicOneToOne.md)
    * [多态一对多关联](components/orm/relation/polymorphicOneToMany.md)
    * [多态多对多关联](components/orm/relation/polymorphicManyToMany.md)
    * [自定义关联](components/orm/relation/relation.md)
    * [前置和后置事件](components/orm/relation/events.md)
  * [数据库迁移](components/orm/migration.md)
* [Redis](components/redis/index.md)
  * [连接与配置](components/redis/index.md)
  * [Redis 使用](components/redis/function.md)
  * [Redis 模型](components/orm/RedisModel.md)
* [内存表模型](components/orm/MemoryTableModel.md)
* [缓存](components/cache/index.md)
  * [使用说明](components/cache/index.md)
  * [文件缓存](components/cache/file.md)
  * [Redis 缓存](components/cache/redis.md)
  * [RedisHash 缓存](components/cache/redisHash.md)
  * [Apcu 缓存](components/cache/apcu.md)
  * [请求上下文缓存](components/cache/requestContext.md)
  * [内存缓存](components/cache/memory.md)
* [日志](components/log/index.md)
* [验证器](components/validation/index.md)
* [门面 (Facade)](components/facade/index.md)
* [请求上下文代理 (RequestContextProxy)](components/requestContextProxy/index.md)
* [Lock 锁](components/lock/index.md)
  * [使用方法](components/lock/index.md)
  * [RedisLock](components/lock/redis.md)
  * [AtomicLock](components/lock/atomic.md)
* [后台任务](components/task/index.md)
* [定时任务](components/task/cron.md)
* [AOP](components/aop/index.md)
* [进程](components/process/index.md)
* [进程池-Swoole](components/process-pool/swoole.md)
* [进程池-imi](components/process-pool/imi.md)
* [定时器](components/timer/index.md)
* [异步执行](components/async/index.md)
* [雪花算法发号器](components/snowflake.md)
* [Phar打包器](components/phar/index.md)
* [imi 组件列表一览](components/list.md)
  * [PostgreSQL](components/db/pgsql.md)
  * [MQTT](components/mqtt/server.md)
  * [gRPC](components/rpc/grpc.md)
  * [消息队列](components/mq/redis.md)
  * [AMQP](components/mq/amqp.md)
  * [Kafka](components/mq/kafka.md)
  * [JWT](components/httpserver/jwt.md)
  * [Smarty 模版引擎](components/smarty.md)
  * [限流](components/rate-limit.md)
  * [跨进程变量共享](components/shared-memory.md)
  * [雪花算法发号器](components/snowflake.md)
  * [Swagger API 文档生成](dev/generate/swagger.md)
  * [Swoole Tracker](components/swoole-tracker.md)
  * [InfluxDB](components/influxdb.md)
  * [TDengine](components/tdengine.md)

## 消息队列

* [Redis](components/mq/redis.md)
* [Redis Stream](components/mq/redisStream.md)
* [RabbitMQ(AMQP)](components/mq/amqp.md)
* [Kafka](components/mq/kafka.md)

## 微服务

* [配置中心](components/config/center.md)
  * [通过配置中心配置连接池](components/config/pool.md)
* [服务注册](components/serviceRegistry/index.md)
* [服务发现（负载均衡）](components/serviceDiscovery/index.md)
* [调用链路追踪](components/tracing/index.md)
  * [Zipkin](components/tracing/opentracing.md#Zipkin)
  * [Jaeger](components/tracing/opentracing.md#Jaeger)
  * [Swoole Tracker](components/swoole-tracker.md)
* [服务指标监控](components/meter/index.md)
  * [Prometheus](components/meter/prometheus.md)
  * [InfluxDB](components/meter/influxdb.md)
  * [TDengine](components/meter/tdengine.md)
* [服务限流](components/rate-limit.md)

## 数据结构

* [通用数据结构](components/struct/enum.md)
  * [Enum(枚举)](components/struct/enum.md)
  * [ArrayData(数组数据)](components/struct/ArrayData.md)
  * [LazyArrayObject(智能数组对象)](components/struct/LazyArrayObject.md)
  * [ArrayList(数组列表)](components/struct/ArrayList.md)
  * [FilterableList(过滤器列表)](components/struct/FilterableList.md)
* [Swoole 数据结构](components/struct/atomic.md)
  * [Atomic(原子性)](components/struct/atomic.md)
  * [Channel(通道)](components/struct/co-channel.md)
  * [MemoryTable(内存表)](components/struct/memory-table.md)

## 通用工具类

* [App 类](utils/app.md)
* [全局函数](utils/functions.md)
* [Imi](utils/Imi.md)
* [Worker](utils/Worker.md)
* [ArrayUtil](utils/ArrayUtil.md)
* [Bit](utils/Bit.md)
* [ClassObject](utils/ClassObject.md)
* [File](utils/File.md)
* [ObjectArrayHelper](utils/ObjectArrayHelper.md)
* [Random](utils/Random.md)
* [Text](utils/Text.md)
* [Pagination](utils/Pagination.md)
* [DateTime](utils/DateTime.md)
* [ServerManager](utils/ServerManager.md)
* [ExpiredStorage](utils/ExpiredStorage.md)
* [服务器工具类](utils/Server.md)

## 开发工具

* [介绍](dev/intro.md)
* [生成工具](dev/generate.md)
  * [模型生成](dev/generate/model.md)
  * [表生成](dev/generate/table.md)
  * [控制器生成](dev/generate/controller.md)
* [自己动手开发命令行工具](dev/tool.md)

## 进阶开发

* [断点调试](adv/debug.md)
* [生产环境性能优化](adv/performance.md)

## 运行环境

* [Docker](production/docker.md)
* [守护进程](production/daemon.md)
* [Swoole Compiler 代码加密](production/swoole-compiler.md)

## Swoole 协程化组件

* [Guzzle](other/guzzle.md)
* [ElasticSearch](other/elasticsearch.md)
* [YurunHttp](other/yurunhttp.md)
* [第三方授权 SDK](other/yurun-oauth-login.md)
* [第三方支付 SDK](other/paysdk.md)
