<?php

declare(strict_types=1);

namespace Imi\Test\Component\Tests;

use Imi\Test\BaseTest;
use Imi\Test\Component\Enum\TestEnum;
use Imi\Test\Component\Enum\TestEnumBean;
use Imi\Test\Component\Enum\TestEnumBeanBacked;
use Imi\Validate\ValidatorHelper;
use PHPUnit\Framework\Assert;

/**
 * @testdox ValidatorHelper
 */
class ValidatorHelperTest extends BaseTest
{
    public function testRegex(): void
    {
        Assert::assertTrue(ValidatorHelper::regex('aBcD', '/^[a-z]+$/i'));
        Assert::assertFalse(ValidatorHelper::regex('aBcD', '/^[a-z]+$/'));
    }

    public function testDecimal(): void
    {
        Assert::assertFalse(ValidatorHelper::decimal(1));
        Assert::assertTrue(ValidatorHelper::decimal(1.1));
        Assert::assertFalse(ValidatorHelper::decimal('1'));
        Assert::assertTrue(ValidatorHelper::decimal('1.1'));

        Assert::assertTrue(ValidatorHelper::decimal(1.0));
        Assert::assertTrue(ValidatorHelper::decimal('1.0'));

        Assert::assertFalse(ValidatorHelper::decimal(1.25, 2));
        Assert::assertFalse(ValidatorHelper::decimal(1.25, null, 1.24));
        Assert::assertTrue(ValidatorHelper::decimal(1.25, 1, 1.25));

        Assert::assertTrue(ValidatorHelper::decimal(1.1, null, null, 1));
        Assert::assertFalse(ValidatorHelper::decimal(1.25, null, null, 1));
        Assert::assertTrue(ValidatorHelper::decimal(1.25, null, null, 2));
        Assert::assertTrue(ValidatorHelper::decimal(1.25, null, null, 3));
    }

    public function testInt(): void
    {
        Assert::assertFalse(ValidatorHelper::int('abc'));
        Assert::assertFalse(ValidatorHelper::int(1.1));
        Assert::assertTrue(ValidatorHelper::int(1));

        Assert::assertFalse(ValidatorHelper::int(5, 6));
        Assert::assertTrue(ValidatorHelper::int(5, 5));

        Assert::assertFalse(ValidatorHelper::int(5, 1, 4));
        Assert::assertTrue(ValidatorHelper::int(5, 1, 5));
    }

    public function testNumber(): void
    {
        Assert::assertTrue(ValidatorHelper::number(1));
        Assert::assertTrue(ValidatorHelper::number(1.1));
        Assert::assertTrue(ValidatorHelper::number('1'));
        Assert::assertTrue(ValidatorHelper::number('1.1'));

        Assert::assertFalse(ValidatorHelper::number(1.25, 2));
        Assert::assertFalse(ValidatorHelper::number(1.25, null, 1.24));
        Assert::assertTrue(ValidatorHelper::number(1.25, 1, 1.25));

        Assert::assertFalse(ValidatorHelper::number(1.1, null, null, 0));
        Assert::assertTrue(ValidatorHelper::number(1.0, null, null, 0));
        Assert::assertTrue(ValidatorHelper::number(1.1, null, null, 1));
        Assert::assertFalse(ValidatorHelper::number(1.25, null, null, 1));
        Assert::assertTrue(ValidatorHelper::number(1.25, null, null, 2));
        Assert::assertTrue(ValidatorHelper::number(1.25, null, null, 3));
        Assert::assertFalse(ValidatorHelper::number(99999.2555, null, null, 3));
    }

    public function testLength(): void
    {
        Assert::assertTrue(ValidatorHelper::length('imi', 1));
        Assert::assertFalse(ValidatorHelper::length('imi', 1, 2));

        Assert::assertTrue(ValidatorHelper::length('imi', 1, 3));
        Assert::assertFalse(ValidatorHelper::length('爱米哎', 1, 3));
    }

    public function testCharLength(): void
    {
        Assert::assertTrue(ValidatorHelper::lengthChar('imi', 1));
        Assert::assertFalse(ValidatorHelper::lengthChar('imi', 1, 2));

        Assert::assertTrue(ValidatorHelper::lengthChar('imi', 1, 3));
        Assert::assertTrue(ValidatorHelper::lengthChar('爱米哎', 1, 3));
    }

    public function testEmptyStr(): void
    {
        Assert::assertTrue(ValidatorHelper::emptyStr(''));
        Assert::assertFalse(ValidatorHelper::emptyStr('imi'));
    }

    public function testNotEmptyStr(): void
    {
        Assert::assertFalse(ValidatorHelper::notEmptyStr(''));
        Assert::assertTrue(ValidatorHelper::notEmptyStr('imi'));
    }

    public function testEmail(): void
    {
        Assert::assertTrue(ValidatorHelper::email('10001@qq.com'));
        Assert::assertTrue(ValidatorHelper::email('a-b@c-d.com.cn'));
        Assert::assertFalse(ValidatorHelper::email('imiphp.com'));
    }

    public function testCnMobile(): void
    {
        Assert::assertTrue(ValidatorHelper::cnMobile('13813814438'));
        Assert::assertFalse(ValidatorHelper::cnMobile('43813814438'));
    }

    public function testMobile(): void
    {
        Assert::assertTrue(ValidatorHelper::mobile('13813814438'));
        Assert::assertFalse(ValidatorHelper::mobile('43813814438'));
    }

    public function testTel(): void
    {
        Assert::assertFalse(ValidatorHelper::tel('13813814438'));
        Assert::assertTrue(ValidatorHelper::tel('400-812-3123'));
        Assert::assertTrue(ValidatorHelper::tel('0510-85111111'));
        Assert::assertTrue(ValidatorHelper::tel('0510-85111111-123'));
    }

    public function testPhone(): void
    {
        Assert::assertTrue(ValidatorHelper::phone('13813814438'));
        Assert::assertTrue(ValidatorHelper::phone('400-812-3123'));
        Assert::assertTrue(ValidatorHelper::phone('0510-85111111'));
        Assert::assertTrue(ValidatorHelper::phone('0510-85111111-123'));
    }

    public function testPostcode(): void
    {
        Assert::assertFalse(ValidatorHelper::postcode('21400'));
        Assert::assertTrue(ValidatorHelper::postcode('214000'));
        Assert::assertFalse(ValidatorHelper::postcode('2140000'));
    }

    public function testUrl(): void
    {
        Assert::assertTrue(ValidatorHelper::url('https://www.imiphp.com'));
        Assert::assertTrue(ValidatorHelper::url('http://www.baidu.com/s?wd=imiphp'));
        Assert::assertTrue(ValidatorHelper::url('ftp://www.qq.com:222/'));
        Assert::assertFalse(ValidatorHelper::url('/1.jpg'));
    }

    /**
     * @testdox QQ
     */
    public function testQQ(): void
    {
        Assert::assertTrue(ValidatorHelper::qq('10001'));
        Assert::assertTrue(ValidatorHelper::qq('369124067'));
        Assert::assertTrue(ValidatorHelper::qq('13813814438'));
        Assert::assertFalse(ValidatorHelper::qq('1000'));
        Assert::assertFalse(ValidatorHelper::qq('138138144384'));
    }

    /**
     * @testdox ipv4
     */
    public function testIPv4(): void
    {
        Assert::assertTrue(ValidatorHelper::ipv4('0.0.0.0'));
        Assert::assertTrue(ValidatorHelper::ipv4('255.255.255.255'));
        Assert::assertTrue(ValidatorHelper::ipv4('127.0.0.1'));

        Assert::assertFalse(ValidatorHelper::ipv4('255.255.255.255.255'));
        Assert::assertFalse(ValidatorHelper::ipv4('256.255.255.255'));
        Assert::assertFalse(ValidatorHelper::ipv4('255.256.255.255'));
        Assert::assertFalse(ValidatorHelper::ipv4('255.255.256.255'));
        Assert::assertFalse(ValidatorHelper::ipv4('255.255.255.256'));

        Assert::assertFalse(ValidatorHelper::ipv4('0.0.0.0.0'));
        Assert::assertFalse(ValidatorHelper::ipv4('-1.0.0.0'));
        Assert::assertFalse(ValidatorHelper::ipv4('0.-2.0.0'));
        Assert::assertFalse(ValidatorHelper::ipv4('0.0.-3.0'));
        Assert::assertFalse(ValidatorHelper::ipv4('0.0.0.-4'));
    }

    /**
     * @testdox ipv6
     */
    public function testIPv6(): void
    {
        Assert::assertTrue(ValidatorHelper::ipv6('::'));
        Assert::assertTrue(ValidatorHelper::ipv6('::1'));
        Assert::assertTrue(ValidatorHelper::ipv6('0000:0000:0000:0000:0000:ffff:c0a8:0a02'));

        Assert::assertFalse(ValidatorHelper::ipv6('-0000:0000:0000:0000:0000:ffff:c0a8:0a02'));
        Assert::assertFalse(ValidatorHelper::ipv6('0000:0000:0000:0000:0000:fffff:c0a8:0a02'));
    }

    /**
     * @testdox ip
     */
    public function testIP(): void
    {
        Assert::assertTrue(ValidatorHelper::ip('0.0.0.0'));
        Assert::assertTrue(ValidatorHelper::ip('255.255.255.255'));
        Assert::assertTrue(ValidatorHelper::ip('127.0.0.1'));

        Assert::assertFalse(ValidatorHelper::ip('255.255.255.255.255'));
        Assert::assertFalse(ValidatorHelper::ip('256.255.255.255'));
        Assert::assertFalse(ValidatorHelper::ip('255.256.255.255'));
        Assert::assertFalse(ValidatorHelper::ip('255.255.256.255'));
        Assert::assertFalse(ValidatorHelper::ip('255.255.255.256'));

        Assert::assertFalse(ValidatorHelper::ip('0.0.0.0.0'));
        Assert::assertFalse(ValidatorHelper::ip('-1.0.0.0'));
        Assert::assertFalse(ValidatorHelper::ip('0.-2.0.0'));
        Assert::assertFalse(ValidatorHelper::ip('0.0.-3.0'));
        Assert::assertFalse(ValidatorHelper::ip('0.0.0.-4'));

        Assert::assertTrue(ValidatorHelper::ip('::'));
        Assert::assertTrue(ValidatorHelper::ip('::1'));
        Assert::assertTrue(ValidatorHelper::ip('0000:0000:0000:0000:0000:ffff:c0a8:0a02'));

        Assert::assertFalse(ValidatorHelper::ip('-0000:0000:0000:0000:0000:ffff:c0a8:0a02'));
        Assert::assertFalse(ValidatorHelper::ip('0000:0000:0000:0000:0000:fffff:c0a8:0a02'));
    }

    public function testBetween(): void
    {
        Assert::assertTrue(ValidatorHelper::between(2, 1, 3));
        Assert::assertFalse(ValidatorHelper::between(1, 1, 3));
        Assert::assertFalse(ValidatorHelper::between(3, 1, 3));
    }

    public function testBetweenEqual(): void
    {
        Assert::assertTrue(ValidatorHelper::betweenEqual(2, 1, 3));
        Assert::assertTrue(ValidatorHelper::betweenEqual(1, 1, 3));
        Assert::assertTrue(ValidatorHelper::betweenEqual(3, 1, 3));
        Assert::assertFalse(ValidatorHelper::between(1, 2, 3));
        Assert::assertFalse(ValidatorHelper::between(3, 1, 2));
    }

    /**
     * @testdox lt
     */
    public function testLT(): void
    {
        Assert::assertTrue(ValidatorHelper::lt(1, 2));
        Assert::assertFalse(ValidatorHelper::lt(2, 2));
        Assert::assertFalse(ValidatorHelper::lt(3, 2));
    }

    /**
     * @testdox ltEqual
     */
    public function testLTEqual(): void
    {
        Assert::assertTrue(ValidatorHelper::ltEqual(1, 2));
        Assert::assertTrue(ValidatorHelper::ltEqual(2, 2));
        Assert::assertFalse(ValidatorHelper::ltEqual(3, 2));
    }

    /**
     * @testdox gt
     */
    public function testGT(): void
    {
        Assert::assertTrue(ValidatorHelper::gt(2, 1));
        Assert::assertFalse(ValidatorHelper::gt(1, 1));
        Assert::assertFalse(ValidatorHelper::gt(0, 1));
    }

    /**
     * @testdox gtEqual
     */
    public function testGTEqual(): void
    {
        Assert::assertTrue(ValidatorHelper::gtEqual(2, 1));
        Assert::assertTrue(ValidatorHelper::gtEqual(1, 1));
        Assert::assertFalse(ValidatorHelper::gtEqual(0, 1));
    }

    public function testEqual(): void
    {
        Assert::assertTrue(ValidatorHelper::equal(1, 1));
        Assert::assertTrue(ValidatorHelper::equal('1', 1));
        Assert::assertFalse(ValidatorHelper::equal(1, 2));
    }

    /**
     * @testdox unequal
     */
    public function testUnEqual(): void
    {
        Assert::assertFalse(ValidatorHelper::unequal(1, 1));
        Assert::assertFalse(ValidatorHelper::unequal('1', 1));
        Assert::assertTrue(ValidatorHelper::unequal(1, 2));
    }

    public function testCompare(): void
    {
        Assert::assertTrue(ValidatorHelper::compare('1', '==', 1));
        Assert::assertFalse(ValidatorHelper::compare(1, '==', 2));

        Assert::assertTrue(ValidatorHelper::compare(1, '===', 1));
        Assert::assertFalse(ValidatorHelper::compare(1, '===', '1'));

        Assert::assertTrue(ValidatorHelper::compare(1, '!=', 2));
        Assert::assertFalse(ValidatorHelper::compare('1', '!=', 1));

        Assert::assertTrue(ValidatorHelper::compare(1, '!==', '1'));
        Assert::assertFalse(ValidatorHelper::compare(1, '!==', 1));

        Assert::assertTrue(ValidatorHelper::compare(1, '<', 2));
        Assert::assertFalse(ValidatorHelper::compare(1, '<', 1));

        Assert::assertTrue(ValidatorHelper::compare(1, '<=', 2));
        Assert::assertTrue(ValidatorHelper::compare(1, '<=', 1));
        Assert::assertFalse(ValidatorHelper::compare(2, '<=', 1));

        Assert::assertTrue(ValidatorHelper::compare(2, '>', 1));
        Assert::assertFalse(ValidatorHelper::compare(1, '>', 1));

        Assert::assertTrue(ValidatorHelper::compare(2, '>=', 1));
        Assert::assertTrue(ValidatorHelper::compare(1, '>=', 1));
        Assert::assertFalse(ValidatorHelper::compare(1, '>=', 2));
    }

    public function testIn(): void
    {
        $list = [1, 2, 3];
        Assert::assertTrue(ValidatorHelper::in(1, $list));
        Assert::assertFalse(ValidatorHelper::in(4, $list));
    }

    public function testNotIn(): void
    {
        $list = [1, 2, 3];
        Assert::assertTrue(ValidatorHelper::notIn(4, $list));
        Assert::assertFalse(ValidatorHelper::notIn(1, $list));
    }

    public function testInEnum(): void
    {
        Assert::assertTrue(ValidatorHelper::inEnum(TestEnum::A, TestEnum::class));
        Assert::assertFalse(ValidatorHelper::inEnum(4, TestEnum::class));
        if (\PHP_VERSION_ID >= 80100)
        {
            Assert::assertTrue(ValidatorHelper::inEnum(TestEnumBean::A, TestEnumBean::class));
            Assert::assertTrue(ValidatorHelper::inEnum('A', TestEnumBean::class));
            Assert::assertFalse(ValidatorHelper::inEnum(4, TestEnumBean::class));
            Assert::assertTrue(ValidatorHelper::inEnum(TestEnumBeanBacked::A, TestEnumBeanBacked::class));
            Assert::assertTrue(ValidatorHelper::inEnum('hello', TestEnumBeanBacked::class));
            Assert::assertFalse(ValidatorHelper::inEnum(4, TestEnumBeanBacked::class));
        }
    }

    public function testNotInEnum(): void
    {
        Assert::assertTrue(ValidatorHelper::notInEnum(4, TestEnum::class));
        Assert::assertFalse(ValidatorHelper::notInEnum(TestEnum::A, TestEnum::class));
        if (\PHP_VERSION_ID >= 80100)
        {
            Assert::assertTrue(ValidatorHelper::notInEnum(4, TestEnumBean::class));
            Assert::assertFalse(ValidatorHelper::notInEnum(TestEnumBean::A, TestEnumBean::class));
            Assert::assertFalse(ValidatorHelper::notInEnum('A', TestEnumBean::class));
            Assert::assertTrue(ValidatorHelper::notInEnum(4, TestEnumBeanBacked::class));
            Assert::assertFalse(ValidatorHelper::notInEnum(TestEnumBeanBacked::A, TestEnumBeanBacked::class));
            Assert::assertFalse(ValidatorHelper::notInEnum('hello', TestEnumBeanBacked::class));
        }
    }

    public function testCnIdcard(): void
    {
        Assert::assertTrue(ValidatorHelper::cnIdcard('632123820927051'));
        Assert::assertTrue(ValidatorHelper::cnIdcard('632123198209270518'));
        Assert::assertFalse(ValidatorHelper::cnIdcard('632123198209270517'));
    }
}
