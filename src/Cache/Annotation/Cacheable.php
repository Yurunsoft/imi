<?php

declare(strict_types=1);

namespace Imi\Cache\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Lock\Annotation\Lockable;

/**
 * 缓存注解.
 *
 * 调用方法前检测是否存在缓存，如果存在直接返回；不存在则执行方法体，然后将返回值存入缓存
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Cacheable extends Base
{
    public function __construct(
        /**
         * 缓存器名称；为null则使用默认缓存器.
         */
        public ?string $name = null,
        /**
         * 缓存键名；支持{id}、{data.name}形式，代入参数；支持{:args}代入所有方法参数的 hash 值；如果为空，则使用类名+方法名+全部参数，序列化后hash.
         */
        public string $key = '',
        /**
         * 缓存超时时间，单位：秒.
         */
        public ?int $ttl = null,
        /**
         * Lock 注解；在调用方法体前后加锁
         */
        public ?Lockable $lockable = null,
        /**
         * 防止缓存击穿；如果设为 true，会在获得锁后，尝试获取缓存，如果缓存存在则不再执行方法体；需要配合 $lockable 属性使用.
         */
        public bool $preventBreakdown = false,
        /**
         * 可以指定 hash 方法，默认为：md5.
         */
        public string $hashMethod = 'md5'
    ) {
    }
}
