<?php

declare(strict_types=1);

namespace Imi\Util;

use Composer\Autoload\ClassLoader;

/**
 * Composer 工具类.
 */
class Composer
{
    private static ?array $classLoaders = null;

    private function __construct()
    {
    }

    /**
     * 获取 Composer ClassLoader 对象
     *
     * @return \Composer\Autoload\ClassLoader[]
     */
    public static function getClassLoaders(bool $force = false): array
    {
        if (!$force && null !== self::$classLoaders)
        {
            return self::$classLoaders;
        }

        return self::$classLoaders = ClassLoader::getRegisteredLoaders();
    }

    /**
     * 获取路径对应的所有命名空间.
     */
    public static function getPathNamespaces(string $path): array
    {
        $realPath = File::absolute($path);
        if (!$realPath)
        {
            return [];
        }
        $result = [];
        foreach (self::getClassLoaders() as $classLoader)
        {
            foreach ($classLoader->getPrefixesPsr4() as $namespace => $namespacePaths)
            {
                foreach ($namespacePaths as $namespacePath)
                {
                    if ($realPath === File::absolute($namespacePath))
                    {
                        $result[] = $namespace;
                    }
                }
            }
        }

        return $result;
    }
}
