<?php

declare(strict_types=1);

namespace Imi\Bean\Parser;

use Imi\Aop\Annotation\BaseInjectValue;
use Imi\Aop\Annotation\Inject;
use Imi\Aop\Annotation\RequestInject;
use Imi\App;
use Imi\Bean\BeanManager;
use Imi\Bean\ReflectionContainer;
use Imi\Server\Annotation\ServerInject;
use Imi\Util\DocBlock;
use phpDocumentor\Reflection\Types\Context;

class BeanParser extends BaseParser
{
    private string $appType = '';

    public function __construct()
    {
        $this->appType = App::getApp()->getType();
    }

    /**
     * {@inheritDoc}
     */
    public function parse(\Imi\Bean\Annotation\Base $annotation, string $className, string $target, string $targetName): void
    {
        if ($annotation instanceof \Imi\Bean\Annotation\Bean)
        {
            $beanName = $annotation->name ?? $className;
            $env = $annotation->env;
            if (null === $env || $this->appType === $env || (\is_array($env) && \in_array($this->appType, $env)))
            {
                BeanManager::add($className, $beanName, $annotation->instanceType, $annotation->recursion, $env);
            }
        }
        elseif ($annotation instanceof BaseInjectValue)
        {
            $annotationClass = \get_class($annotation);
            switch ($annotationClass)
            {
                case Inject::class:
                case RequestInject::class:
                case ServerInject::class:
                    /** @var Inject|RequestInject $annotation */
                    if ('' === $annotation->name && self::TARGET_PROPERTY === $target)
                    {
                        $propRef = ReflectionContainer::getPropertyReflection($className, $targetName);
                        if (method_exists($propRef, 'hasType') && $propRef->hasType())
                        {
                            $type = $propRef->getType();
                            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin())
                            {
                                $annotation->name = $type->getName();
                                break;
                            }
                        }
                        $comment = $propRef->getDocComment();
                        if (false === $comment)
                        {
                            throw new \RuntimeException(sprintf('@%s in %s::$%s must set name', $annotationClass, $className, $target));
                        }
                        else
                        {
                            $docblock = DocBlock::getDocBlock($comment, new Context($propRef->getDeclaringClass()->getNamespaceName()));
                            $tag = $docblock->getTagsByName('var')[0] ?? null;
                            if ($tag)
                            {
                                $annotation->name = $tag->getType()->__toString();
                            }
                        }
                    }
                    break;
            }
            BeanManager::addPropertyInject($className, $targetName, $annotationClass, $annotation->toArray());
        }
    }
}
