<?php

declare(strict_types=1);

namespace Imi\Grpc\Util;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\Annotation\Bean;
use Imi\Bean\ReflectionContainer;
use Imi\Bean\ReflectionUtil;
use Imi\Server\Http\Route\Annotation\Controller;
use Imi\Util\DocBlock;
use phpDocumentor\Reflection\Types\Context;

/**
 * @Bean(name="GrpcInterfaceManager", recursion=false)
 */
class GrpcInterfaceManager
{
    /**
     * 绑定的服务接口.
     */
    protected array $binds = [];

    /**
     * 接口集合.
     */
    private array $interfaces = [];

    /**
     * 服务集合.
     */
    private array $services = [];

    public function __init(): void
    {
        foreach (AnnotationManager::getAnnotationPoints(Controller::class, 'class') as $point)
        {
            $refClass = ReflectionContainer::getClassReflection($point->getClass());
            foreach ($refClass->getInterfaces() as $interface)
            {
                if ($serviceName = $this->parseServiceNameByInterface($interface))
                {
                    // 控制器是 Grpc
                    $interfaceName = $interface->getName();
                    $this->bind($interfaceName, $serviceName);
                    break;
                }
            }
        }
        foreach ($this->binds as $interfaceName)
        {
            $this->bind($interfaceName);
        }
    }

    /**
     * @param string|\ReflectionClass $interface
     */
    public function bind($interface, ?string $serviceName = null): void
    {
        if (\is_string($interface))
        {
            $interfaceName = $interface;
            $interface = new \ReflectionClass($interfaceName);
        }
        else
        {
            $interfaceName = $interface->getName();
        }
        if (null === $serviceName)
        {
            $serviceName = $this->parseServiceNameByInterface($interface);
        }
        // 控制器是 Grpc
        $methods = [];
        $interfaceItem = ['serviceName' => $serviceName, 'methods' => &$methods];
        $this->services[$serviceName] = [
            'interfaceName' => $interfaceName,
        ];
        foreach ($interface->getMethods() as $method)
        {
            $param = $method->getParameters()[0] ?? null;
            if (!$param || !($type = $param->getType()))
            {
                continue;
            }
            $requestClass = ReflectionUtil::getTypeCode($type, $interface->getName());

            $docComment = $method->getDocComment();
            if (false === $docComment)
            {
                $responseClass = '';
            }
            else
            {
                $docblock = DocBlock::getDocBlock($docComment, new Context($interface->getNamespaceName()));
                // @phpstan-ignore-next-line
                $responseClass = (string) $docblock->getTagsByName('return')[0]->getType();
            }

            $methods[$method->getName()] = [
                'request'  => $requestClass,
                'response' => $responseClass,
            ];
        }
        $this->interfaces[$interfaceName] = $interfaceItem;
    }

    /**
     * @param string|\ReflectionClass $interface
     */
    public function parseServiceNameByInterface($interface): ?string
    {
        if (\is_string($interface))
        {
            $interface = new \ReflectionClass($interface);
        }
        if (preg_match('/Protobuf type <code>([^<]+)<\/code>/', $interface->getDocComment(), $matches))
        {
            return $matches[1];
        }

        return null;
    }

    /**
     * 获取请求类.
     */
    public function getRequest(string $interface, string $method): string
    {
        if (!isset($this->interfaces[$interface]) && null !== ($serviceName = $this->parseServiceNameByInterface($interface)))
        {
            $this->bind($interface, $serviceName);
        }

        return $this->interfaces[$interface]['methods'][$method]['request'] ?? '';
    }

    /**
     * 获取响应类.
     */
    public function getResponse(string $interface, string $method): string
    {
        if (!isset($this->interfaces[$interface]) && null !== ($serviceName = $this->parseServiceNameByInterface($interface)))
        {
            $this->bind($interface, $serviceName);
        }

        return $this->interfaces[$interface]['methods'][$method]['response'] ?? '';
    }

    /**
     * 获取服务名称.
     */
    public function getServiceName(string $interface): string
    {
        if (!isset($this->interfaces[$interface]) && null !== ($serviceName = $this->parseServiceNameByInterface($interface)))
        {
            $this->bind($interface, $serviceName);
        }

        return $this->interfaces[$interface]['serviceName'] ?? '';
    }

    /**
     * 获取接口名称.
     */
    public function getInterface(string $serviceName): string
    {
        return $this->services[$serviceName]['interfaceName'] ?? '';
    }
}
