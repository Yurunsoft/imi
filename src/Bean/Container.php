<?php

declare(strict_types=1);

namespace Imi\Bean;

use Imi\Bean\Annotation\Bean;
use Imi\Bean\Exception\ContainerException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * Bean 对象们.
     */
    private array $beanObjects = [];

    /**
     * 单例对象们.
     */
    private array $singletonObjects = [];

    /**
     * 绑定列表.
     *
     * @var array[]|callable[]
     */
    private array $binds = [];

    public function __construct(array $binds = [])
    {
        $this->binds = $binds;
    }

    /**
     * 从容器中获取实例对象，如果不存在则实例化.
     *
     * @param string $id        标识符
     * @param mixed  ...$params
     *
     * @return mixed entry
     *
     * @throws \Psr\Container\NotFoundExceptionInterface  没有找到对象
     * @throws \Psr\Container\ContainerExceptionInterface 检索时出错
     */
    public function get(string $id, ...$params)
    {
        return self::__newInstance($id, $params, true);
    }

    /**
     * 每次调用都实例化返回新的对象.
     *
     * @param string $id        标识符
     * @param mixed  ...$params
     *
     * @return mixed entry
     *
     * @throws \Psr\Container\NotFoundExceptionInterface  没有找到对象
     * @throws \Psr\Container\ContainerExceptionInterface 检索时出错
     */
    public function newInstance(string $id, ...$params)
    {
        return self::__newInstance($id, $params, false);
    }

    /**
     * @param string $id 标识符
     *
     * @return mixed
     *
     * @throws \Psr\Container\NotFoundExceptionInterface  没有找到对象
     * @throws \Psr\Container\ContainerExceptionInterface 检索时出错
     */
    private function __newInstance(string $id, array $params, bool $allowStore)
    {
        // 单例中有数据，且无实例化参数时直接返回单例
        $beanObjects = &$this->beanObjects;
        if ($allowStore && isset($beanObjects[$id]) && empty($params))
        {
            return $beanObjects[$id];
        }

        if ('' === $id)
        {
            throw new ContainerException('$id can not be a empty string value');
        }

        $object = null;

        $binds = $this->binds;
        $originId = $id;

        while (!$object)
        {
            if (isset($binds[$id]))
            {
                $data = $binds[$id];
                if (\is_callable($data))
                {
                    $object = $data($originId, ...$params);
                    if ($object)
                    {
                        $data = null;
                    }
                }
                else
                {
                    $className = $data['className'];
                    if (class_exists($className))
                    {
                        if ($data['recursion'])
                        {
                            $object = BeanFactory::newInstanceNoInit($className, ...$params);
                        }
                        else
                        {
                            $object = BeanFactory::newInstance($className, ...$params);
                        }
                    }
                    else
                    {
                        $id = $className;
                    }
                }
            }
            else
            {
                $data = BeanManager::get($id);
                if ($data)
                {
                    $className = $data['className'];
                    if (class_exists($className))
                    {
                        if ($data['recursion'])
                        {
                            $object = BeanFactory::newInstanceNoInit($className, ...$params);
                        }
                        else
                        {
                            $object = BeanFactory::newInstance($className, ...$params);
                        }
                    }
                    else
                    {
                        $id = $className;
                    }
                }
                elseif (class_exists($id))
                {
                    $object = BeanFactory::newInstanceNoInit($id, ...$params);
                }
                else
                {
                    throw new ContainerException(sprintf('%s not found', $id));
                }
            }
        }

        // 传参实例化强制不使用单例
        if ($stored = ($allowStore && empty($params) && Bean::INSTANCE_TYPE_SINGLETON === ($data['instanceType'] ?? Bean::INSTANCE_TYPE_SINGLETON)))
        {
            if (isset($beanObjects[$originId]))
            {
                // 防止类 __construct() 方法有协程上下文切换，导致单例被覆盖
                return $beanObjects[$originId];
            }
            $beanObjects[$originId] = $object;
        }

        if ($data['recursion'] ?? true)
        {
            // @phpstan-ignore-next-line
            BeanFactory::initInstance($object, $params);
            if ($stored && $object !== $beanObjects[$originId])
            {
                // 防止类 __init() 方法有协程上下文切换，导致单例被覆盖
                return $beanObjects[$originId];
            }
        }

        // @phpstan-ignore-next-line
        return $object;
    }

    /**
     * 从容器中获取实例对象，如果不存在则实例化.
     *
     * 此方法实例化的对象，AOP、注解等都对它不产生作用
     *
     * @param string $id        标识符
     * @param mixed  ...$params
     *
     * @return mixed entry
     *
     * @throws \Psr\Container\NotFoundExceptionInterface  没有找到对象
     * @throws \Psr\Container\ContainerExceptionInterface 检索时出错
     */
    public function getSingleton(string $id, ...$params)
    {
        // 单例中有数据，且无实例化参数时直接返回单例
        $singletonObjects = &$this->singletonObjects;
        if (isset($singletonObjects[$id]) && empty($params))
        {
            return $singletonObjects[$id];
        }

        if ('' === $id)
        {
            throw new ContainerException('$id can not be a empty string value');
        }

        $object = null;
        $binds = $this->binds;
        $originId = $id;

        while (!$object)
        {
            if (isset($binds[$id]))
            {
                $data = $binds[$id];
                if (\is_callable($data))
                {
                    $object = $data($originId, ...$params);
                    if ($object)
                    {
                        $data = null;
                    }
                }
                else
                {
                    $className = $data['className'];
                    if (class_exists($className))
                    {
                        $object = new $className(...$params);
                    }
                    else
                    {
                        $id = $className;
                    }
                }
            }
            else
            {
                $data = BeanManager::get($id);
                if ($data)
                {
                    $className = $data['className'];
                    if (class_exists($className))
                    {
                        $object = new $className(...$params);
                    }
                    else
                    {
                        $id = $className;
                    }
                }
                elseif (class_exists($id))
                {
                    $object = new $id(...$params);
                }
                else
                {
                    throw new ContainerException(sprintf('%s not found', $id));
                }
            }
        }

        // 传参实例化强制不使用单例
        if (empty($params) && Bean::INSTANCE_TYPE_SINGLETON === ($data['instanceType'] ?? Bean::INSTANCE_TYPE_SINGLETON))
        {
            $singletonObjects[$originId] = $object;
        }

        // @phpstan-ignore-next-line
        return $object;
    }

    /**
     * 设置实例.
     */
    public function set(string $id, object $object): void
    {
        $this->beanObjects[$id] = $object;
    }

    /**
     * 实例对象是否存在.
     *
     * @param string $id 标识符
     */
    public function has(string $id): bool
    {
        return '' !== $id && isset($this->beanObjects[$id]);
    }

    /**
     * 绑定名称和类名.
     */
    public function bind(string $name, string $class, string $instanceType = Bean::INSTANCE_TYPE_SINGLETON, bool $recursion = true): void
    {
        $this->binds[$name] = [
            'className'    => $class,
            'instanceType' => $instanceType,
            'recursion'    => $recursion,
        ];
    }

    /**
     * 绑定回调.
     */
    public function bindCallable(string $name, callable $callable): void
    {
        $this->binds[$name] = $callable;
    }

    /**
     * 设置绑定列表.
     */
    public function setBinds(array $binds): void
    {
        $result = [];
        foreach ($binds as $key => $value)
        {
            if (\is_string($value))
            {
                $value = [
                    'className'    => $value,
                    'instanceType' => Bean::INSTANCE_TYPE_SINGLETON,
                    'recursion'    => true,
                ];
            }
            $result[$key] = $value;
        }
        $this->binds = $result;
    }

    /**
     * 追加绑定列表.
     */
    public function appendBinds(array $binds): void
    {
        foreach ($binds as $key => $value)
        {
            if (\is_string($value))
            {
                $value = [
                    'className'    => $value,
                    'instanceType' => Bean::INSTANCE_TYPE_SINGLETON,
                    'recursion'    => true,
                ];
            }
            $this->binds[$key] = $value;
        }
    }

    /**
     * Get 绑定列表.
     */
    public function getBinds(): array
    {
        return $this->binds;
    }

    /**
     * 返回一个新的容器对象，容器内无对象，仅有列表关联.
     *
     * @return static
     */
    public function newSubContainer(): self
    {
        return new static($this->binds);
    }
}
