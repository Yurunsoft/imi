<?php

declare(strict_types=1);

namespace Imi\Test\Component\Tests;

use Imi\Cache\CacheManager;
use Imi\Test\BaseTest;
use PHPUnit\Framework\Assert;

abstract class BaseCacheTest extends BaseTest
{
    protected string $cacheName;

    protected bool $supportTTL = true;

    public function testSetAndGet(): void
    {
        Assert::assertTrue(CacheManager::set($this->cacheName, 'imi', 'nb'));
        Assert::assertEquals('nb', CacheManager::get($this->cacheName, 'imi'));
    }

    /**
     * @testdox Set TTL
     */
    public function testSetTTL(): void
    {
        if (!$this->supportTTL)
        {
            $this->markTestSkipped('Handler does not support TTL');
        }
        Assert::assertTrue(CacheManager::set($this->cacheName, 'imi', 'nb', 1));
        Assert::assertEquals('nb', CacheManager::get($this->cacheName, 'imi'));
        sleep(2);
        Assert::assertEquals('none', CacheManager::get($this->cacheName, 'imi', 'none'));
    }

    public function testSetMultiple(): void
    {
        $values = [
            'k1'    => 'v1',
            'k2'    => 'v2',
        ];
        Assert::assertTrue(CacheManager::setMultiple($this->cacheName, $values));
        $getValues = CacheManager::getMultiple($this->cacheName, array_keys($values));
        Assert::assertEquals($values, $getValues);
    }

    /**
     * @testdox Set multiple TTL
     */
    public function testSetMultipleTTL(): void
    {
        if (!$this->supportTTL)
        {
            $this->markTestSkipped('Handler does not support TTL');
        }
        $this->go(function () {
            $values = [
                'k1'    => 'v1',
                'k2'    => 'v2',
            ];
            Assert::assertTrue(CacheManager::setMultiple($this->cacheName, $values, 1));
            $getValues = CacheManager::getMultiple($this->cacheName, array_keys($values));
            Assert::assertEquals($values, $getValues);
            sleep(2);
            Assert::assertEquals([
                'k1'    => 'none',
                'k2'    => 'none',
            ], CacheManager::getMultiple($this->cacheName, array_keys($values), 'none'));
        }, null, 3);
    }

    public function testDelete(): void
    {
        Assert::assertTrue(CacheManager::set($this->cacheName, 'imi', 'nb'));
        Assert::assertEquals('nb', CacheManager::get($this->cacheName, 'imi'));
        Assert::assertTrue(CacheManager::delete($this->cacheName, 'imi'));
        Assert::assertNull(CacheManager::get($this->cacheName, 'imi'));
    }

    public function testDeleteMultiple(): void
    {
        $values = [
            'k1'    => 'v1',
            'k2'    => 'v2',
        ];
        Assert::assertTrue(CacheManager::setMultiple($this->cacheName, $values));
        $getValues = CacheManager::getMultiple($this->cacheName, array_keys($values));
        Assert::assertEquals($values, $getValues);

        Assert::assertTrue(CacheManager::deleteMultiple($this->cacheName, array_keys($values)));
        Assert::assertEquals([
            'k1'    => null,
            'k2'    => null,
        ], CacheManager::getMultiple($this->cacheName, array_keys($values)));
    }

    public function testHas(): void
    {
        Assert::assertTrue(CacheManager::set($this->cacheName, 'imi', 'nb'));
        Assert::assertTrue(CacheManager::has($this->cacheName, 'imi'));
        Assert::assertTrue(CacheManager::delete($this->cacheName, 'imi'));
        Assert::assertFalse(CacheManager::has($this->cacheName, 'imi'));
    }

    public function testClear(): void
    {
        $values = [
            'k1'    => 'v1',
            'k2'    => 'v2',
        ];
        Assert::assertTrue(CacheManager::setMultiple($this->cacheName, $values));
        $getValues = CacheManager::getMultiple($this->cacheName, array_keys($values));
        Assert::assertEquals($values, $getValues);

        Assert::assertTrue(CacheManager::clear($this->cacheName));
        Assert::assertEquals([
            'k1'    => null,
            'k2'    => null,
        ], CacheManager::getMultiple($this->cacheName, array_keys($values)));
    }
}
