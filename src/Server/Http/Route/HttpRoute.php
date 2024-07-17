<?php

declare(strict_types=1);

namespace Imi\Server\Http\Route;

use Imi\Bean\Annotation\Bean;
use Imi\Bean\BeanFactory;
use Imi\Log\Log;
use Imi\Server\Http\Message\Request;
use Imi\Server\Http\Route\Annotation\Route as RouteAnnotation;
use Imi\Server\View\Parser\ViewParser;
use Imi\Util\DelayServerBeanCallable;
use Imi\Util\Imi;
use Imi\Util\ObjectArrayHelper;
use Imi\Util\Uri;

/**
 * @Bean(name="HttpRoute", recursion=false)
 */
class HttpRoute
{
    private ?Router $router = null;

    /**
     * 忽略 URL 规则大小写.
     */
    protected bool $ignoreCase = false;

    /**
     * 智能尾部斜杠，无论是否存在都匹配.
     */
    protected bool $autoEndSlash = false;

    public function __construct()
    {
        $this->router = new Router();
    }

    public static function isStaticPath(string $path): bool
    {
        return !str_contains($path, '{') || !str_contains($path, '}');
    }

    /**
     * 增加路由规则.
     *
     * @param string                                  $path       路径规则
     * @param mixed                                   $callable   回调
     * @param \Imi\Server\Http\Route\Annotation\Route $annotation 路由定义注解，可选
     */
    public function addRule(string $path, $callable, RouteAnnotation $annotation = null, array $options = []): void
    {
        [$view, $viewOption] = ViewParser::getInstance()->getByCallable($callable);
        $checkCallables = [];
        if (null !== $annotation->paramsGet)
        {
            $checkCallables[] = [self::class, 'checkParamsGet'];
        }
        if (null !== $annotation->paramsPost)
        {
            $checkCallables[] = [self::class, 'checkParamsPost'];
        }
        if (null !== $annotation->paramsBody)
        {
            $checkCallables[] = [self::class, 'checkParamsBody'];
        }
        if (null !== $annotation->header)
        {
            $checkCallables[] = [self::class, 'checkHeader'];
        }
        if (null !== $annotation->requestMime)
        {
            $checkCallables[] = [self::class, 'checkRequestMime'];
        }
        if (null !== $annotation->domain)
        {
            $checkCallables[] = [self::class, 'checkDomain'];
        }
        if (null === $annotation)
        {
            $annotation = new RouteAnnotation([
                'url' => $path,
            ]);
        }
        $routeItem = new RouteItem($annotation, $callable, $view, $viewOption, $options);
        if (isset($options['middlewares']))
        {
            $routeItem->middlewares = $options['middlewares'];
        }
        if (isset($options['wsConfig']))
        {
            $routeItem->wsConfig = $options['wsConfig'];
        }
        $ignoreCase = $annotation->ignoreCase ?? $this->ignoreCase;
        if (!$ignoreCase && self::isStaticPath($path))
        {
            $this->router->addStatic($path, $callable, $annotation->method, $ignoreCase, $checkCallables, [
                'routeItem' => $routeItem,
            ]);
            if ($this->autoEndSlash && !str_ends_with($path, '/'))
            {
                $routeItem = clone $routeItem;
                $routeItem->annotation->url .= '/';
                $this->router->addStatic($path . '/', $callable, $annotation->method, $ignoreCase, $checkCallables, [
                    'routeItem' => $routeItem,
                ]);
            }
        }
        else
        {
            $this->router->add($path, $callable, $annotation->method, $ignoreCase, $checkCallables, [
                'routeItem' => $routeItem,
            ]);
            if ($this->autoEndSlash && !str_ends_with($path, '/'))
            {
                $routeItem = clone $routeItem;
                $routeItem->annotation->url .= '/';
                $this->router->add($path . '/', $callable, $annotation->method, $ignoreCase, $checkCallables, [
                    'routeItem' => $routeItem,
                ]);
            }
        }
    }

    /**
     * 增加路由规则，直接使用注解方式.
     *
     * @param mixed $callable
     */
    public function addRuleAnnotation(RouteAnnotation $annotation, $callable, array $options = []): void
    {
        $this->addRule($annotation->url, $callable, $annotation, $options);
    }

    /**
     * 清空路由规则.
     */
    public function clearRules(): void
    {
        $this->router->setDynamicRoutes([])->setStaticRoutes([]);
    }

    /**
     * 获取路由规则.
     */
    public function getRules(): array
    {
        $router = $this->router;

        return [
            'dynamic' => $router->getDynamicRoutes(),
            'static'  => $router->getStaticRoutes(),
        ];
    }

    /**
     * 设置路由规则.
     */
    public function setRules(array $rules): void
    {
        $router = $this->router;
        $router->setDynamicRoutes($rules['dynamic'] ?? []);
        $router->setStaticRoutes($rules['static'] ?? []);
    }

    /**
     * 路由规则是否为空.
     */
    public function isEmpty(): bool
    {
        $router = $this->router;

        return !$router->getStaticRoutes() && !$router->getDynamicRoutes();
    }

    /**
     * 路由解析处理.
     */
    public function parse(Request $request): ?RouteResult
    {
        $result = $this->router->dispatch($request);
        switch ($result[0])
        {
            case Router::FOUND:
                $item = $result[1][Router::ROUTE_DATA]['routeItem'];

                return new RouteResult(spl_object_id($item), $item, $result[2]);
        }

        return null;
    }

    /**
     * 检查验证域名是否匹配.
     */
    public static function checkDomain(Request $request, array $route, array &$data, Router $router): bool
    {
        /** @var RouteItem $routeItem */
        $routeItem = $route[Router::ROUTE_DATA]['routeItem'];
        $domain = $routeItem->annotation->domain;
        if (null === $domain)
        {
            return true;
        }
        $uriDomain = Uri::getDomain($request->getUri());
        foreach ((array) $domain as $rule)
        {
            if (self::isStaticPath($rule))
            {
                if (0 === strcasecmp($rule, $uriDomain))
                {
                    return true;
                }
            }
            else
            {
                $rule = $router->getPathPattern($rule, true, $fields);
                // 域名匹配不区分大小写
                if (preg_match_all($rule, $uriDomain, $matches) > 0)
                {
                    foreach ($fields as $i => $fieldName)
                    {
                        $data[$fieldName] = $matches[$i + 1][0];
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 检查验证GET参数是否匹配.
     */
    public static function checkParamsGet(Request $request, array $route, array &$data, Router $router): bool
    {
        /** @var RouteItem $routeItem */
        $routeItem = $route[Router::ROUTE_DATA]['routeItem'];
        $params = $routeItem->annotation->paramsGet;
        if (null === $params)
        {
            return true;
        }

        return Imi::checkCompareRules($params, static fn (string $name) => $request->get($name));
    }

    /**
     * 检查验证POST参数是否匹配.
     */
    public static function checkParamsPost(Request $request, array $route, array &$data, Router $router): bool
    {
        /** @var RouteItem $routeItem */
        $routeItem = $route[Router::ROUTE_DATA]['routeItem'];
        $params = $routeItem->annotation->paramsPost;
        if (null === $params)
        {
            return true;
        }

        return Imi::checkCompareRules($params, static fn (string $name) => $request->post($name));
    }

    /**
     * 检查验证 JSON、XML 参数是否匹配.
     */
    public static function checkParamsBody(Request $request, array $route, array &$data, Router $router): bool
    {
        /** @var RouteItem $routeItem */
        $routeItem = $route[Router::ROUTE_DATA]['routeItem'];
        $annotation = $routeItem->annotation;
        $params = $annotation->paramsBody;
        if (null === $params)
        {
            return true;
        }

        $paramsBodyMultiLevel = $annotation->paramsBodyMultiLevel;
        $parsedBody = $request->getParsedBody();
        $isObject = \is_object($parsedBody);

        return Imi::checkCompareRules($params, static function (string $name) use ($parsedBody, $isObject, $paramsBodyMultiLevel) {
            if ($paramsBodyMultiLevel)
            {
                return ObjectArrayHelper::get($parsedBody, $name);
            }
            elseif ($isObject)
            {
                return $parsedBody->{$name} ?? null;
            }
            else
            {
                return $parsedBody[$name] ?? null;
            }
        });
    }

    /**
     * 检查验证请求头是否匹配.
     */
    public static function checkHeader(Request $request, array $route, array &$data, Router $router): bool
    {
        /** @var RouteItem $routeItem */
        $routeItem = $route[Router::ROUTE_DATA]['routeItem'];
        $header = $routeItem->annotation->header;
        if (null === $header)
        {
            return true;
        }

        return Imi::checkCompareRules($header, static fn (string $name) => $request->getHeaderLine($name));
    }

    /**
     * 检查验证请求媒体类型是否匹配.
     */
    public static function checkRequestMime(Request $request, array $route, array &$data, Router $router): bool
    {
        /** @var RouteItem $routeItem */
        $routeItem = $route[Router::ROUTE_DATA]['routeItem'];
        $requestMime = $routeItem->annotation->requestMime;
        if (null === $requestMime)
        {
            return true;
        }

        return Imi::checkCompareValues($requestMime, $request->getHeaderLine('Content-Type'));
    }

    /**
     * Get 忽略 URL 规则大小写.
     */
    public function getIgnoreCase(): bool
    {
        return $this->ignoreCase;
    }

    /**
     * Get 智能尾部斜杠，无论是否存在都匹配.
     */
    public function getAutoEndSlash(): bool
    {
        return $this->autoEndSlash;
    }

    /**
     * 检查重复路由.
     */
    public function checkDuplicateRoutes(): void
    {
        $router = $this->router;
        $routerRoutes = $router->getStaticRoutes();
        $routerRoutes[] = $router->getDynamicRoutes();
        $map = [];
        $firstMap = [];
        foreach ($routerRoutes as $routes)
        {
            if (isset($routes[1]))
            {
                foreach ($routes as $route)
                {
                    /** @var RouteItem $routeItem */
                    $routeItem = $route[Router::ROUTE_DATA]['routeItem'];
                    $string = (string) $routeItem->annotation;
                    if (isset($map[$string]))
                    {
                        if (!isset($firstMap[$string]))
                        {
                            $firstMap[$string] = false;
                            $this->logDuplicated($map[$string]);
                        }
                        $this->logDuplicated($routeItem);
                    }
                    else
                    {
                        $map[$string] = $routeItem;
                    }
                }
            }
        }
    }

    private function logDuplicated(RouteItem $routeItem): void
    {
        $callable = $routeItem->callable;
        if ($callable instanceof DelayServerBeanCallable)
        {
            $logString = sprintf('Route "%s" duplicated (%s::%s)', $routeItem->annotation->url, $callable->getBeanName(), $callable->getMethodName());
        }
        elseif (\is_array($callable))
        {
            $class = BeanFactory::getObjectClass($callable[0]);
            $method = $callable[1];
            $logString = sprintf('Route "%s" duplicated (%s::%s)', $routeItem->annotation->url, $class, $method);
        }
        else
        {
            $logString = sprintf('Route "%s" duplicated', $routeItem->annotation->url);
        }
        Log::warning($logString);
    }

    public function getRouter(): Router
    {
        return $this->router;
    }
}
