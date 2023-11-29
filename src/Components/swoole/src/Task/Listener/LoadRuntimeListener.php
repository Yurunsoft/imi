<?php

declare(strict_types=1);

namespace Imi\Swoole\Task\Listener;

use Imi\Config;
use Imi\Event\IEventListener;
use Imi\Swoole\Task\TaskManager;

class LoadRuntimeListener implements IEventListener
{
    /**
     * {@inheritDoc}
     */
    public function handle(\Imi\Event\Contract\IEvent $e): void
    {
        $config = Config::get('@app.imi.runtime.swoole', []);
        if (!($config['task'] ?? true))
        {
            return;
        }
        $data = $e->getData()['data']['task'] ?? [];
        TaskManager::setMap($data['task'] ?? []);
    }
}
