<?php

declare(strict_types=1);

namespace Imi\Swoole\Server\Listener;

use Imi\Bean\Annotation\Listener;
use Imi\Event\IEventListener;
use Imi\Server\Event\PipeMessageEvent;
use Imi\Swoole\Util\Co\ChannelContainer;

/**
 * 发送给所有 Worker 进程的连接-响应.
 */
#[Listener(eventName: 'IMI.PIPE_MESSAGE.sendRawToAllResponse')]
class OnSendRawToAllResponse implements IEventListener
{
    /**
     * @param PipeMessageEvent $e
     */
    public function handle(\Imi\Event\Contract\IEvent $e): void
    {
        $data = $e->data['data'];
        if (ChannelContainer::hasChannel($data['messageId']))
        {
            ChannelContainer::push($data['messageId'], $data);
        }
    }
}
