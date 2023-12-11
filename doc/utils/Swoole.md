# SwooleWorker

[toc]

## v2.x

**类名:** `Imi\Swoole\SwooleWorker`

**继承:** `Imi\Worker` (详见：[链接](/v3.0/utils/Worker.html))

### 方法

#### isTask

是否为 task 进程

`public static function isTask(): bool`

#### getTaskWorkerNum

获取 task 进程数量

`public static function getTaskWorkerNum(): int`

#### isWorkerStartAppComplete

是否 imi.main_server.worker.start.app 事件执行完毕

`public static function isWorkerStartAppComplete(): bool`

#### getManagerPid

获取服务器 manager 进程 PID

`public static function getManagerPid(): int`

#### isWorkerIdProcess

返回 workerId 是否是用户进程

`public static function isWorkerIdProcess(int $workerId): bool`
