# 开始一个新项目

[toc]

## 项目初始化

imi 提供了多个项目模板可供选择：

创建 Http Server 项目：`composer create-project imiphp/project-http:~3.0.0`

创建 WebSocket Server 项目：`composer create-project imiphp/project-websocket:~3.0.0`

创建 TCP Server 项目：`composer create-project imiphp/project-tcp:~3.0.0`

创建 UDP Server 项目：`composer create-project imiphp/project-udp:~3.0.0`

创建 gRPC 项目：`composer create-project imiphp/project-grpc:~3.0.0`

创建 MQTT Server 项目：`composer create-project imiphp/project-mqtt:~3.0.0`

运行命令后，会在当前目录创建一个新的 imi 项目，并下载所需的依赖。

> 如何运行请看上面项目中的`README.md`

> 项目最终使用什么协议，和上面的命令行无绝对关系。命令行创建项目只是提供一个快捷途径，服务的通信协议，可以通过修改配置文件来更换。

## 流程说明

在 imi 框架中，一个项目分为一个主服务器和多个子服务器。

其中，主服务器为必须，子服务器为可选。子服务器通过监听端口实现，一般不推荐开启过多的子服务器。

在项目中，如果你需要做一些初始化的事情，你可以在服务器的命名空间目录下创建一个`Main.php`，并把类命名为`Main`

项目的`Main`必须继承`Imi\Main\AppBaseMain`类。

```php
<?php

declare(strict_types=1);

namespace ImiApp;

class Main extends \Imi\Main\AppBaseMain
{
    public function __init(): void
    {
    }
}
```

服务器的`Main`必须继承`Imi\Main\BaseMain`类。

```php
<?php

namespace ImiApp\ApiServer;

use Imi\Main\BaseMain;

class Main extends BaseMain
{
    public function __init(): void
    {
    }
}
```

当然，如果你不需要初始化，就不需要创建了
