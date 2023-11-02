<?php

declare(strict_types=1);

namespace Imi\Server\WebSocket\Route\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Annotation\Parser;

/**
 * WebSocket 控制器注解.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
#[Parser(className: \Imi\Server\WebSocket\Parser\WSControllerParser::class)]
class WSController extends Base
{
    public function __construct(
        /**
         * http 路由；如果设置，则只有握手指定 http 路由，才可以触发该 WebSocket 路由.
         */
        public ?string $route = null,
        /**
         * 指定当前控制器允许哪些服务器使用；支持字符串或数组，默认为 null 则不限制.
         *
         * @var string|string[]|null
         */
        public $server = null
    ) {
    }
}
