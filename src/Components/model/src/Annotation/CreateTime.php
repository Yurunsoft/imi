<?php

declare(strict_types=1);

namespace Imi\Model\Annotation;

use Imi\Bean\Annotation\Base;

/**
 * 创建时间.
 *
 * 有此注解就表示启用.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class CreateTime extends Base
{
    public function __construct(
        /**
         * 时间精度.
         *
         * 仅 bigint 有效，例：1000为毫秒
         */
        public int $timeAccuracy = 1000
    ) {
    }
}
