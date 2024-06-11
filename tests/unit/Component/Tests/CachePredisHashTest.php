<?php

declare(strict_types=1);

/**
 * @testdox Cache RedisHash Handler
 */
class CachePredisHashTest extends \Imi\Test\Component\Tests\BaseCacheTestCase
{
    protected string $cacheName = 'predisHash';

    protected bool $supportTTL = false;
}
