<?php

declare(strict_types=1);

namespace Imi\Test\Component\Enum;

use Imi\Enum\Annotation\EnumItem;
use Imi\Enum\BaseEnum;

class TestEnum extends BaseEnum
{
    #[EnumItem(text: '甲', other: 'a1')]
    public const A = 1;

    #[EnumItem(text: '乙', other: 'b2')]
    public const B = 2;

    #[EnumItem(text: '丙', other: 'c3')]
    public const C = 3;
}
