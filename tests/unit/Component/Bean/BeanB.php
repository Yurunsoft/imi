<?php

declare(strict_types=1);

namespace Imi\Test\Component\Bean;

use Imi\Bean\Annotation\Bean;

#[Bean(name: 'BeanB', env: 'cli')]
class BeanB
{
}
