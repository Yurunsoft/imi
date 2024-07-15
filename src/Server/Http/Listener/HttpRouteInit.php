<?php

declare(strict_types=1);

namespace Imi\Server\Http\Listener;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\RequestContext;
use Imi\Server\Http\Annotation\ExtractData;
use Imi\Server\Http\Annotation\RequestParam;
use Imi\Server\Http\Parser\ControllerParser;
use Imi\Server\Http\Route\Annotation\Action;
use Imi\Server\Http\Route\Annotation\Middleware;
use Imi\Server\Http\Route\Annotation\Route;
use Imi\Server\Http\Route\HttpRoute;
use Imi\Server\Protocol;
use Imi\Server\Route\TMiddleware;
use Imi\Server\ServerManager;
use Imi\Server\WebSocket\Route\Annotation\WSConfig;
use Imi\Util\DelayServerBeanCallable;
use Imi\Worker;

/**
 * http服务器路由初始化.
 */
class HttpRouteInit implements IEventListener
{
    use TMiddleware;

    /**
     * {@inheritDoc}
     */
    public function handle(EventParam $e): void
    {
        $this->parseAnnotations();
    }

    /**
     * 处理注解路由.
     */
    protected function parseAnnotations(): void
    {
        $controllerParser = ControllerParser::getInstance();
        $context = RequestContext::getContext();
        $originServer = $context['server'] ?? null;
        foreach (ServerManager::getServers() as $name => $server)
        {
            if (!\in_array($server->getProtocol(), [Protocol::HTTP, Protocol::WEBSOCKET]))
            {
                continue;
            }
            $context['server'] = $server;
            /** @var HttpRoute $route */
            $route = $server->getBean('HttpRoute');
            $autoEndSlash = $route->getAutoEndSlash();
            foreach ($controllerParser->getByServer($name) as $className => $classItem)
            {
                /** @var \Imi\Server\Http\Route\Annotation\Controller $classAnnotation */
                $classAnnotation = $classItem->getAnnotation();
                if (null !== $classAnnotation->server && !\in_array($name, (array) $classAnnotation->server))
                {
                    continue;
                }
                // 类中间件
                $classMiddlewares = [];
                /** @var Middleware $middleware */
                foreach (AnnotationManager::getClassAnnotations($className, Middleware::class) as $middleware)
                {
                    $classMiddlewares = array_merge($classMiddlewares, $this->getMiddlewares($middleware->middlewares, $name));
                }
                foreach (AnnotationManager::getMethodsAnnotations($className, Action::class) as $methodName => $_)
                {
                    $annotations = AnnotationManager::getMethodAnnotations($className, $methodName, [
                        Route::class,
                        Middleware::class,
                        ExtractData::class,
                        WSConfig::class,
                        RequestParam::class,
                    ]);
                    $routeAnnotations = $annotations[Route::class];
                    if ($routeAnnotations)
                    {
                        $routes = $routeAnnotations;
                    }
                    else
                    {
                        $routes = [
                            new Route([
                                'url' => $methodName,
                            ]),
                        ];
                    }
                    // 方法中间件
                    $methodMiddlewares = [];
                    if ($annotations[Middleware::class])
                    {
                        /** @var Middleware $middleware */
                        foreach ($annotations[Middleware::class] as $middleware)
                        {
                            $methodMiddlewares = array_merge($methodMiddlewares, $this->getMiddlewares($middleware->middlewares, $name));
                        }
                    }
                    // 最终中间件
                    $middlewares = array_values(array_unique(array_merge($classMiddlewares, $methodMiddlewares)));

                    /** @var Route[] $routes */
                    foreach ($routes as $routeItem)
                    {
                        if (null === $routeItem->url)
                        {
                            $routeItem->url = $methodName;
                        }
                        $prefix = $classAnnotation->prefix;
                        if ('' != $prefix)
                        {
                            if (!isset($routeItem->url[0]) || '/' !== $routeItem->url[0])
                            {
                                if (isset($routeItem->url[1]) && str_starts_with($routeItem->url, './'))
                                {
                                    $prefixHasSlash = '/' === ($prefix[-1] ?? '');
                                    $routeItem->url = $prefix . substr($routeItem->url, $prefixHasSlash ? 2 : 1);
                                }
                                else
                                {
                                    $routeItem->url = $prefix . $routeItem->url;
                                }
                            }
                        }
                        $extractData = [];
                        // @deprecated 3.0
                        if ($annotations[ExtractData::class])
                        {
                            /** @var ExtractData $item */
                            foreach ($annotations[ExtractData::class] as $item)
                            {
                                $extractData[$item->to] = [
                                    'name'     => $item->name,
                                    'default'  => $item->default,
                                    'required' => false,
                                ];
                            }
                        }
                        if ($annotations[RequestParam::class])
                        {
                            /** @var RequestParam $item */
                            foreach ($annotations[RequestParam::class] as $item)
                            {
                                $extractData[$item->param] = [
                                    'name'     => $item->name,
                                    'default'  => $item->default,
                                    'required' => $item->required,
                                ];
                            }
                        }
                        if ($methodRequestParamAnnotations = AnnotationManager::getMethodParametersAnnotations($className, $methodName, RequestParam::class))
                        {
                            foreach ($methodRequestParamAnnotations as $paramName => $requestParams)
                            {
                                $item = $requestParams[0];
                                $extractData[$paramName] = [
                                    'name'     => $item->name,
                                    'default'  => $item->default,
                                    'required' => $item->required,
                                ];
                            }
                        }
                        $routeCallable = new DelayServerBeanCallable($server, $className, $methodName);
                        $options = [
                            'middlewares'   => $middlewares,
                            'wsConfig'      => $annotations[WSConfig::class][0] ?? null,
                            'extractData'   => $extractData,
                        ];
                        $route->addRuleAnnotation($routeItem, $routeCallable, $options);
                    }
                }
            }
            if (0 === Worker::getWorkerId())
            {
                $route->checkDuplicateRoutes();
            }
            unset($context['server']);
        }
        $context['server'] = $originServer;
    }
}
