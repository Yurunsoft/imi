<?php

declare(strict_types=1);

namespace Imi\Dev\PHPStan;

use Composer\Autoload\ClassLoader;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileFinderResult;
use PHPStan\File\FileHelper;
use PHPStan\File\PathNotFoundException;

class FileFinder extends \PHPStan\File\FileFinder
{
    private FileExcluder $fileExcluder;
    private FileHelper $fileHelper;
    private array $fileExtensions;

    private string $finderClass;

    /**
     * @param string[] $fileExtensions
     */
    public function __construct(
        FileExcluder $fileExcluder,
        FileHelper $fileHelper,
        array $fileExtensions
    ) {
        $this->fileExcluder = $fileExcluder;
        $this->fileHelper = $fileHelper;
        $this->fileExtensions = $fileExtensions;

        /** @var ClassLoader $composer */
        $composer = require 'phar://phpstan.phar/vendor/autoload.php';

        foreach ($composer->getPrefixesPsr4() as $name => $paths)
        {
            if (str_ends_with($name, 'Symfony\\Component\\Finder\\'))
            {
                $this->finderClass = $name . 'Finder';
            }
        }
        if (!isset($this->finderClass))
        {
            throw new \RuntimeException('[Symfony\\Component\\Finder\\Finder] not found');
        }
    }

    /**
     * @param string[] $paths
     */
    public function findFiles(array $paths): FileFinderResult
    {
        $onlyFiles = true;
        $files = [];
        foreach ($paths as $path)
        {
            if (is_file($path))
            {
                $files[] = $this->fileHelper->normalizePath($path);
            }
            elseif (!file_exists($path))
            {
                throw new PathNotFoundException($path);
            }
            else
            {
                $finder = new $this->finderClass();
                // 此行注释，防止无限套娃
                // $finder->followLinks();
                foreach ($finder->files()->name('*.{' . implode(',', $this->fileExtensions) . '}')->in($path) as $fileInfo)
                {
                    $files[] = $this->fileHelper->normalizePath($fileInfo->getPathname());
                    $onlyFiles = false;
                }
            }
        }

        $files = array_values(array_filter($files, fn (string $file): bool => !$this->fileExcluder->isExcludedFromAnalysing($file)));

        return new FileFinderResult($files, $onlyFiles);
    }
}
