<?php

declare(strict_types=1);

namespace Imi\Test\Component\Event\Classes;

use Imi\Event\TEvent;

class TestClass
{
    use TEvent;

    public function test1(): mixed
    {
        $return = null;
        $this->trigger('test1', [
            'name'   => 'imi',
            'return' => &$return,
        ], $this);

        return $return;
    }

    public function test2(): mixed
    {
        $return = null;
        $this->trigger('test2', [
            'name'   => 'imi',
            'return' => &$return,
        ], $this);

        return $return;
    }

    public function test3(): mixed
    {
        $return = null;
        $this->trigger('test3', [
            'name'   => 'imi',
            'return' => &$return,
        ], $this);

        return $return;
    }
}
