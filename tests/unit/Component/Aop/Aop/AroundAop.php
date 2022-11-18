<?php

declare(strict_types=1);

namespace Imi\Test\Component\Aop\Aop;

use Imi\Aop\Annotation\Around;
use Imi\Aop\Annotation\Aspect;
use Imi\Aop\Annotation\PointCut;
use Imi\Aop\AroundJoinPoint;
use Imi\Bean\BeanFactory;
use PHPUnit\Framework\Assert;

/**
 * @Aspect
 */
class AroundAop
{
    /**
     * @Around
     *
     * @PointCut(
     *     allow={
     *         "Imi\Test\Component\Aop\Classes\TestAroundClass::test"
     *     }
     * )
     *
     * @return mixed
     */
    public function injectAroundAop(AroundJoinPoint $joinPoint)
    {
        Assert::assertEquals([1], $joinPoint->getArgs());
        Assert::assertEquals('test', $joinPoint->getMethod());
        Assert::assertEquals(\Imi\Test\Component\Aop\Classes\TestAroundClass::class, BeanFactory::getObjectClass($joinPoint->getTarget()));
        Assert::assertEquals('around', $joinPoint->getType());
        $joinPoint->setArgs([2]);
        $result = $joinPoint->proceed();
        Assert::assertEquals(2, $result);
        $result = $joinPoint->proceed([3]);
        Assert::assertEquals(3, $result);

        return 4;
    }
}
