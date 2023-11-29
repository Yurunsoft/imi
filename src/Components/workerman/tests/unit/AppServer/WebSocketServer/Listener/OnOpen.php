<?php

declare(strict_types=1);

namespace Imi\Workerman\Test\AppServer\WebSocketServer\Listener;

use Imi\Bean\Annotation\Listener;
use Imi\ConnectionContext;
use Imi\Event\IEventListener;

#[Listener(eventName: 'IMI.WORKERMAN.SERVER.WEBSOCKET.CONNECT')]
class OnOpen implements IEventListener
{
    /**
     * {@inheritDoc}
     */
    public function handle(\Imi\Event\Contract\IEvent $e): void
    {
        ConnectionContext::set('requestUri', (string) ConnectionContext::get('uri'));
    }
}
