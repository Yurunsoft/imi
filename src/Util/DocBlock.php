<?php

declare(strict_types=1);

namespace Imi\Util;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Location;
use phpDocumentor\Reflection\Types\Context;

class DocBlock
{
    use \Imi\Util\Traits\TStaticClass;

    private static ?DocBlockFactoryInterface $factory = null;

    public static function getFactory(): DocBlockFactoryInterface
    {
        if (null === self::$factory)
        {
            self::$factory = DocBlockFactory::createInstance();
        }

        return self::$factory;
    }

    /**
     * @param object|string $docblock a string containing the DocBlock to parse or an object supporting the getDocComment method (such as a ReflectionClass object)
     */
    public static function getDocBlock($docblock, ?Context $context = null, ?Location $location = null): DocBlockFactoryInterface
    {
        return self::getFactory()->create($docblock, $context, $location);
    }
}
