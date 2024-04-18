<?php

declare(strict_types=1);

namespace Imi\Util;

use phpDocumentor\Reflection\DocBlock as RealDocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Location;
use phpDocumentor\Reflection\Types\Context;

class DocBlock
{
    use \Imi\Util\Traits\TStaticClass;

    private static ?DocBlockFactory $factory = null;

    public static function getFactory(): DocBlockFactory
    {
        if (null === self::$factory)
        {
            // @phpstan-ignore-next-line
            return self::$factory = DocBlockFactory::createInstance();
        }

        return self::$factory;
    }

    /**
     * @param object|string $docblock a string containing the DocBlock to parse or an object supporting the getDocComment method (such as a ReflectionClass object)
     */
    public static function getDocBlock($docblock, ?Context $context = null, ?Location $location = null): RealDocBlock
    {
        return self::getFactory()->create($docblock, $context, $location);
    }
}
