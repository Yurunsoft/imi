<?php

declare(strict_types=1);

namespace Imi\Test\Component\Partial\Classes;

use Imi\Bean\Annotation\Bean;

#[Bean(name: 'PartialClassA')]
class PartialClassA
{
    public function test1(): int
    {
        return 1;
    }
}
