# 调用链路追踪

[TOC]

为了应对各种复杂的业务，开发工程师开始采用敏捷开发、持续集成等开发方式。系统架构也从单机大型软件演化成微服务架构。微服务构建在不同的软件集上，这些软件模块可能是由不同团队开发的，可能使用不同的编程语言来实现，还可能发布在多台服务器上。因此，如果一个服务出现问题，可能导致几十个应用都出现服务异常。

分布式追踪系统可以记录请求范围内的信息，例如一次远程方法调用的执行过程和耗时，是我们排查系统问题和系统性能的重要工具。

> 所有的链路追踪都或多或少有性能损耗，请根据实际需要在生产环境中使用！

**支持的中间件：**

* [x] [Swoole Tracker](/v3.0/components/swoole-tracker.html)

* [x] [Zipkin](/v3.0/components/tracing/opentracing.html#Zipkin)

* [x] [Jaeger](/v3.0/components/tracing/opentracing.html#Jaeger)

……
