<?php

declare(strict_types=1);

namespace Imi\Swoole\Server\Listener;

use Imi\Bean\Annotation\Listener;
use Imi\Event\IEventListener;
use Imi\Swoole\Util\Co\ChannelContainer;

/**
 * 发送给指定连接-响应.
 */
#[Listener(eventName: 'IMI.PIPE_MESSAGE.sendToClientIdsResponse')]
class OnSendToClientIdsResponse implements IEventListener
{
    /**
     * {@inheritDoc}
     */
    public function handle(\Imi\Event\Contract\IEvent $e): void
    {
        $data = $e->getData()['data'];
        if (ChannelContainer::hasChannel($data['messageId']))
        {
            ChannelContainer::push($data['messageId'], $data);
        }
    }
}
