<?php

declare(strict_types=1);

namespace Imi\Core\Component\Listener;

use Imi\Bean\Annotation;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\BeanManager;
use Imi\Bean\PartialManager;
use Imi\Config;
use Imi\Core\Component\ComponentManager;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;

class LoadRuntimeListener implements IEventListener
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventParam $e): void
    {
        $config = Config::get('@app.imi.runtime', []);
        if (!($config['component'] ?? true))
        {
            return;
        }
        ComponentManager::setComponents($e->getData()['data']['component'] ?? []);
    }
}
