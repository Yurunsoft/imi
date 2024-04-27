<?php

declare(strict_types=1);

namespace Imi\Model\Annotation;

use Imi\Bean\Annotation\Base;

/**
 * 字符串字段分割为列表.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ListSeparator extends Base
{
    public function __construct(
        /**
         * 用于分割的字符串.
         */
        public string $separator = ','
    ) {
    }
}
