<?php

declare(strict_types=1);

namespace Imi\Test\Component\Event\Listener;

use Imi\Bean\Annotation\ClassEventListener;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\Test\Component\Event\Classes\TestClass;
use PHPUnit\Framework\Assert;

#[ClassEventListener(className: \Imi\Test\Component\Event\Classes\TestClass::class, eventName: 'test1')]
class ClassEventTestListener implements IEventListener
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventParam $e): void
    {
        Assert::assertEquals('test1', $e->getEventName());
        Assert::assertEquals(TestClass::class, \get_class($e->getTarget()));
        $data = $e->getData();
        Assert::assertEquals('imi', $data['name']);
        $data['return'] = 19260817;
    }
}
