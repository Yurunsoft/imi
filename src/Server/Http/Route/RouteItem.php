<?php

declare(strict_types=1);

namespace Imi\Server\Http\Route;

use Imi\Server\Http\Route\Annotation\Route;
use Imi\Server\View\Annotation\BaseViewOption;
use Imi\Server\View\Annotation\View;
use Imi\Server\WebSocket\Route\Annotation\WSConfig;

class RouteItem
{
    /**
     * 注解.
     */
    public ?Route $annotation = null;

    /**
     * 回调.
     *
     * @var callable
     */
    public $callable;

    /**
     * 中间件列表.
     */
    public array $middlewares = [];

    /**
     * WebSocket 配置.
     *
     * @var WSConfig
     */
    public ?WSConfig $wsConfig = null;

    /**
     * 其它配置项.
     */
    public array $options = [];

    /**
     * 视图注解.
     */
    public ?View $view = null;

    /**
     * 视图配置注解.
     */
    public ?BaseViewOption $viewOption = null;

    public function __construct(Route $annotation, callable $callable, View $view, ?BaseViewOption $viewOption = null, array $options = [])
    {
        $this->annotation = $annotation;
        $this->callable = $callable;
        $this->view = $view;
        $this->viewOption = $viewOption;
        $this->options = $options;
    }

    public function __clone()
    {
        if ($this->annotation)
        {
            $this->annotation = clone $this->annotation;
        }
    }
}
