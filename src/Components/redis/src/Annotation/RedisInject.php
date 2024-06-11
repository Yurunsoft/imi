<?php

declare(strict_types=1);

namespace Imi\Redis\Annotation;

use Imi\Aop\Annotation\RequestInject;
use Imi\Bean\Annotation\Inherit;
use Imi\Redis\RedisManager;

/**
 * 连接池对象注入.
 *
 * {@inheritdoc}
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
#[Inherit]
class RedisInject extends RequestInject
{
    /**
     * 获取注入值的真实值
     */
    public function getRealValue(): mixed
    {
        return RedisManager::getInstance($this->name);
    }
}
