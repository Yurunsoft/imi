<?php

declare(strict_types=1);

namespace Imi\Test\Component\Tests;

use Imi\Bean\BeanFactory;
use Imi\Cache\Handler\Redis;
use PHPUnit\Framework\Assert;

/**
 * @testdox Cache Redis Handler
 */
class CacheRedisTest extends BaseCacheTestCase
{
    protected string $cacheName = 'redis';

    public function testMultipleWithPrefix(): void
    {
        $cache = BeanFactory::newInstance(Redis::class, [
            'poolName'           => 'redis_test',
            'prefix'             => 'imi-test:',
            'formatHandlerClass' => \Imi\Util\Format\Json::class,
        ]);

        $value = bin2hex(random_bytes(8));
        $values = [
            'k1' => 'v1' . $value,
            'k2' => 'v2' . $value,
            'k3' => 'v3' . $value,
        ];
        Assert::assertTrue($cache->setMultiple($values));
        $getValues = $cache->getMultiple([0 => 'k1', 2 => 'k2', 'A' => 'k3']);
        Assert::assertEquals($values, $getValues);

        $this->go(static function () use ($cache): void {
            $value = bin2hex(random_bytes(8));

            $values = [
                'k1' => 'v1' . $value,
                'k2' => 'v2' . $value,
            ];
            Assert::assertTrue($cache->setMultiple($values, 1));
            $getValues = $cache->getMultiple(array_keys_string($values));
            Assert::assertEquals($values, $getValues);
            sleep(2);
            Assert::assertEquals([
                'k1' => 'none',
                'k2' => 'none',
            ], $cache->getMultiple(array_keys_string($values), 'none'));
        }, null, 3);
    }
}
