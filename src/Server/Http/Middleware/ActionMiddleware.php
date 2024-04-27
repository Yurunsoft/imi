<?php

declare(strict_types=1);

namespace Imi\Server\Http\Middleware;

use Imi\Bean\Annotation\Bean;
use Imi\Bean\BeanFactory;
use Imi\Bean\ReflectionContainer;
use Imi\RequestContext;
use Imi\Server\Annotation\ServerInject;
use Imi\Server\Http\Message\Contract\IHttpResponse;
use Imi\Server\Http\Message\Request;
use Imi\Server\Http\Route\RouteResult;
use Imi\Server\Http\Struct\ActionMethodItem;
use Imi\Server\Session\Session;
use Imi\Server\View\View;
use Imi\Util\DelayServerBeanCallable;
use Imi\Util\ObjectArrayHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Bean(name: 'ActionMiddleware')]
class ActionMiddleware implements MiddlewareInterface
{
    #[ServerInject(name: 'View')]
    protected View $view;

    /**
     * 动作方法参数缓存.
     *
     * @var \ReflectionParameter[]
     */
    private array $actionMethodParams = [];

    /**
     * 动作方法缓存.
     *
     * @var ActionMethodItem[][]
     */
    private array $actionMethodCaches = [];

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 获取Response对象
        $response = $handler->handle($request);
        // 获取路由结果
        RequestContext::set('response', $response);
        $result = $this->dispatch($request, $response, $handler);
        if (null === $result)
        {
            return $response;
        }

        return $result;
    }

    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, ?RequestHandlerInterface $handler = null): ?ResponseInterface
    {
        $context = RequestContext::getContext();
        // 获取路由结果
        if (null === ($result = $context['routeResult']))
        {
            throw new \RuntimeException('RequestContent not found routeResult');
        }
        /** @var \Imi\Server\Http\Route\RouteResult $result */
        // 执行动作
        // @phpstan-ignore-next-line
        $actionResult = ($result->callable)(...$this->prepareActionParams($request, $result));
        // 视图
        if ($actionResult instanceof IHttpResponse)
        {
            return $actionResult;
        }
        $routeItem = $result->routeItem;
        if ($actionResult instanceof \Imi\Server\View\Annotation\View)
        {
            // 动作返回的值是@View注解
            $viewAnnotation = $actionResult;
            $viewOption = $viewAnnotation->option;
        }
        else
        {
            // 获取对应动作的视图注解
            $viewAnnotation = $routeItem->view;
            if ($viewAnnotation->data && \is_array($actionResult))
            {
                // 动作返回值是数组，合并到视图注解
                $data = array_merge($viewAnnotation->data, $actionResult);
            }
            else
            {
                $data = $actionResult;
            }
        }

        // 视图渲染
        return $this->view->render($viewAnnotation, $viewOption ?? $routeItem->viewOption, $data ?? $viewAnnotation->data, $context['response']);
    }

    /**
     * 准备调用action的参数.
     */
    private function prepareActionParams(Request $request, RouteResult $routeResult): array
    {
        $routeResultId = $routeResult->id;
        if (isset($this->actionMethodCaches[$routeResultId]))
        {
            $actionMethodCache = $this->actionMethodCaches[$routeResultId];
        }
        else
        {
            $callable = $routeResult->callable;
            // 根据动作回调类型获取反射
            if (\is_array($callable))
            {
                if (\is_string($callable[0]))
                {
                    $class = $callable[0];
                }
                else
                {
                    $class = BeanFactory::getObjectClass($callable[0]);
                }
                $method = $callable[1];
                $actionMethodParams = &$this->actionMethodParams;
                if (isset($actionMethodParams[$class][$method]))
                {
                    $params = $actionMethodParams[$class][$method];
                }
                else
                {
                    $ref = ReflectionContainer::getMethodReflection($class, $method);
                    $params = $actionMethodParams[$class][$method] = $ref->getParameters();
                }
            }
            elseif ($callable instanceof DelayServerBeanCallable)
            {
                $ref = ReflectionContainer::getMethodReflection($callable->getBeanName(), $callable->getMethodName());
                $params = $ref->getParameters();
            }
            elseif (!$callable instanceof \Closure)
            {
                $ref = new \ReflectionFunction($callable);
                $params = $ref->getParameters();
            }
            if (!isset($params) || !$params)
            {
                return $this->actionMethodCaches[$routeResultId] = [];
            }
            $actionMethodCache = [];
            /** @var \ReflectionParameter[] $params */
            foreach ($params as $param)
            {
                $hasDefault = $param->isDefaultValueAvailable();
                $actionMethodCache[] = new ActionMethodItem(
                    $param->name,
                    $hasDefault,
                    $hasDefault ? $param->getDefaultValue() : null,
                    $param->allowsNull(),
                    $param->getType()
                );
            }
            $this->actionMethodCaches[$routeResultId] = $actionMethodCache;
        }
        if (!$actionMethodCache)
        {
            return [];
        }
        $result = [];
        $get = $request->get();
        $post = $request->post();
        $parsedBody = $request->getParsedBody();
        $parsedBodyIsObject = \is_object($parsedBody);
        if ($parsedBodyIsObject)
        {
            $parsedBodyIsArray = false;
        }
        else
        {
            $parsedBodyIsArray = \is_array($parsedBody);
        }

        if ($extractData = $routeResult->routeItem->options['extractData'] ?? [])
        {
            $allData = [
                '$get'      => $get,
                '$post'     => $post,
                '$body'     => $parsedBody,
                '$headers'  => [],
                '$cookie'   => $request->getCookieParams(),
                '$session'  => Session::get(),
                '$this'     => $request,
            ];
            $headers = &$allData['$headers'];
            foreach ($request->getHeaders() as $name => $values)
            {
                $headers[$name] = implode(', ', $values);
            }
        }
        else
        {
            $allData = [];
        }

        /** @var ActionMethodItem[] $actionMethodCache */
        foreach ($actionMethodCache as $actionMethodCacheItem)
        {
            $paramName = $actionMethodCacheItem->getName();
            if (isset($extractData[$paramName]))
            {
                $item = $extractData[$paramName];
                // @phpstan-ignore-next-line
                if ($item['required'] && !ObjectArrayHelper::exists($allData, $item['name']))
                {
                    throw new \InvalidArgumentException(sprintf('Missing parameter: %s', $paramName));
                }
                $value = ObjectArrayHelper::get($allData, $item['name'], $item['default']);
            }
            elseif (isset($routeResult->params[$paramName]))
            {
                // 路由解析出来的参数
                $value = $routeResult->params[$paramName];
            }
            elseif (isset($post[$paramName]))
            {
                // post
                $value = $post[$paramName];
            }
            elseif (isset($get[$paramName]))
            {
                // get
                $value = $get[$paramName];
            }
            elseif ($parsedBodyIsObject && isset($parsedBody->{$paramName}))
            {
                $value = $parsedBody->{$paramName};
            }
            elseif ($parsedBodyIsArray && isset($parsedBody[$paramName]))
            {
                $value = $parsedBody[$paramName];
            }
            elseif ($actionMethodCacheItem->hasDefault())
            {
                $value = $actionMethodCacheItem->getDefault();
            }
            elseif ($actionMethodCacheItem->allowNull())
            {
                $value = null;
            }
            elseif (($type = $actionMethodCacheItem->getType()) && (UploadedFileInterface::class === $type || is_subclass_of($type, UploadedFileInterface::class)))
            {
                $uploadedFiles ??= $request->getUploadedFiles();
                if (!isset($uploadedFiles[$paramName]))
                {
                    throw new \InvalidArgumentException(sprintf('Missing uploaded file: %s', $paramName));
                }
                /** @var UploadedFileInterface $value */
                $value = $uploadedFiles[$paramName];
                if (0 !== $value->getError())
                {
                    throw new \RuntimeException(sprintf('Upload file failed. error:%d', $value->getError()));
                }
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('Missing parameter: %s', $paramName));
            }
            if (null !== $value)
            {
                switch ($actionMethodCacheItem->getType())
                {
                    case 'int':
                        $value = (int) $value;
                        break;
                    case 'float':
                        $value = (float) $value;
                        break;
                    case 'bool':
                        $value = (bool) $value;
                        break;
                }
            }
            $result[] = $value;
        }

        return $result;
    }
}
