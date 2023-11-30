<?php

declare(strict_types=1);

namespace Imi\Swoole\Server\Event\Param;

use Imi\Event\CommonEvent;
use Imi\Swoole\Server\Contract\ISwooleServer;

class ManagerStartEventParam extends CommonEvent
{
    public function __construct(
        /**
         * 服务器对象
         */
        public readonly ?ISwooleServer $server = null
    ) {
        parent::__construct('IMI.MAIN_SERVER.MANAGER.START', $server);
    }
}
