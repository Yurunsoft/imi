<?php

declare(strict_types=1);

namespace Imi\Dev;

use FilesystemIterator;
use Imi\Cli\ImiCommand;
use function implode;
use function method_exists;
use Swoole\Event;
use Swoole\Runtime;
use Symfony\Component\Process\Process;

class Plugin
{
    public static function dev(): void
    {
        $componentsDir = \dirname(__DIR__) . '/src/Components';
        $output = ImiCommand::getOutput();
        $isSwoole = \extension_loaded('swoole');
        if ($isSwoole)
        {
            Runtime::enableCoroutine();
        }
        foreach (new FilesystemIterator($componentsDir, FilesystemIterator::SKIP_DOTS) as $dir)
        {
            if (!$dir->isDir())
            {
                continue;
            }
            $fileName = $dir->getPathname() . '/composer.json';
            if (is_file($fileName))
            {
                $function = function () use ($output, $dir) {
                    $output->writeln("[Update <info>{$dir->getBasename()}</info>]");
                    $cmd = [
                        \PHP_BINARY,
                        realpath($_SERVER['SCRIPT_FILENAME']),
                        'update',
                        '--no-interaction',
                        '--prefer-dist',
                        '--no-progress',
                    ];
                    // 兼容 symfony process < 3.3
                    if (method_exists(Process::class, 'fromShellCommandline'))
                    {
                        $p = new Process($cmd, $dir->getPathname(), null, null, 0);
                    }
                    else
                    {
                        $p = new Process([], $dir->getPathname(), null, null, 0);
                        /* @phpstan-ignore-next-line */
                        $p->setCommandLine(implode(' ', $cmd));
                    }
                    $p->run(function ($type, $buffer) {
                        echo $buffer;
                    });

                    $result = $p->isSuccessful() ? '<info>success</info>' : "<error>fail({$p->getExitCode()})</error>";
                    $output->writeln("[Update <info>{$dir->getBasename()}</info>]: {$result}");
                };
                if ($isSwoole)
                {
                    go($function);
                }
                else
                {
                    $function();
                }
            }
        }
        if ($isSwoole)
        {
            Event::wait();
        }
    }
}
