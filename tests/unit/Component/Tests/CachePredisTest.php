<?php

declare(strict_types=1);

namespace Imi\Test\Component\Tests;

/**
 * @testdox Cache Redis Handler
 */
class CachePredisTest extends BaseCacheTestCase
{
    protected string $cacheName = 'predis';
}
