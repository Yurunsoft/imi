<?php

declare(strict_types=1);

namespace Imi\Test\Component\Async;

use Imi\Async\Annotation\Async;
use Imi\Async\Annotation\Defer;
use Imi\Async\Annotation\DeferAsync;
use Imi\Async\AsyncResult;
use Imi\Async\Contract\IAsyncResult;
use Imi\Bean\Annotation\Bean;

/**
 * @Bean("AsyncTester")
 */
class AsyncTester
{
    /**
     * @Async
     */
    public function testAsync1(float $a, float $b): IAsyncResult
    {
        return new AsyncResult($a + $b);
    }

    /**
     * @Async
     *
     * @return float|IAsyncResult
     */
    public function testAsync2(float $a, float $b)
    {
        return $a + $b;
    }

    /**
     * @Defer
     */
    public function testDefer1(float $a, float $b): IAsyncResult
    {
        return new AsyncResult($a + $b);
    }

    /**
     * @Defer
     *
     * @return float|IAsyncResult
     */
    public function testDefer2(float $a, float $b)
    {
        return $a + $b;
    }

    /**
     * @DeferAsync
     */
    public function testDeferAsync1(float $a, float $b): IAsyncResult
    {
        return new AsyncResult($a + $b);
    }

    /**
     * @DeferAsync
     *
     * @return float|IAsyncResult
     */
    public function testDeferAsync2(float $a, float $b)
    {
        return $a + $b;
    }

    /**
     * @Async
     */
    public function testException(): IAsyncResult
    {
        throw new \RuntimeException('gg');
    }
}
