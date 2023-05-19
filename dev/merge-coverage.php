<?php

declare(strict_types=1);

use Imi\Util\File;
use SebastianBergmann\CodeCoverage\Report\Clover;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;

\define('ROOT_DIR', \dirname(__DIR__));

require ROOT_DIR . '/vendor/autoload.php';

foreach (File::enumPHPFile(ROOT_DIR . '/dev/cover') as $file)
{
    if (is_file($path = $file[0]))
    {
        echo 'Loading coverage ', $path, '...', \PHP_EOL;
        $tmpCodeCoverage = include $path;
        if (isset($codeCoverage))
        {
            /** @var \SebastianBergmann\CodeCoverage\CodeCoverage $codeCoverage */
            $codeCoverage->merge($tmpCodeCoverage);
        }
        else
        {
            $codeCoverage = $tmpCodeCoverage;
        }
    }
}

if (!isset($codeCoverage))
{
    throw new \RuntimeException('No coverage data');
}

echo 'Generating coverage report...', \PHP_EOL;

switch ($_SERVER['argv'][1] ?? 'html')
{
    case 'clover':
        (new Clover())->process($codeCoverage, ROOT_DIR . '/tests/coverage.xml');
        break;
    case 'php':
        (new \SebastianBergmann\CodeCoverage\Report\PHP())->process($codeCoverage, ROOT_DIR . '/tests/coverage.php');
        break;
    default:
        // html
        (new Facade())->process($codeCoverage, ROOT_DIR . '/tests/html-coverage');
        break;
}
