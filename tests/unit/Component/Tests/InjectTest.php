<?php

declare(strict_types=1);

namespace Imi\Test\Component\Tests;

use Imi\Aop\Annotation\Inject;
use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\Test\BaseTest;
use Imi\Test\Component\Inject\Classes\TestTAutoInject;
use PHPUnit\Framework\Assert;

/**
 * @Bean
 *
 * @testdox Inject
 */
class InjectTest extends BaseTest
{
    public function testInject(): void
    {
        $testTAutoInject = new TestTAutoInject();
        $value = $testTAutoInject->getTestInjectValue();
        Assert::assertNotNull($value);
        $value->test();
    }

    public function testInject2(): void
    {
        $testTAutoInject = new TestTAutoInject();
        $value = $testTAutoInject->getTestInjectValue();
        Assert::assertNotNull($value);
        $value->test2();
    }

    public function testArg(): void
    {
        /** @var \Imi\Test\Component\Inject\Classes\TestArg $test */
        $test = App::getBean('TestArg');
        $test->test(123);
    }
}
