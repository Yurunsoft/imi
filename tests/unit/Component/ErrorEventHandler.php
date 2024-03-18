<?php

declare(strict_types=1);

namespace Imi\Test\Component;

use Imi\Log\AbstractErrorEventHandler;
use Imi\Log\Log;
use Psr\Log\LogLevel;

class ErrorEventHandler extends AbstractErrorEventHandler
{
    public function handleError(int $errNo, string $errStr, string $errFile, int $errLine): void
    {
        // 解决 idea 单文件测试引发异常
        // 解决部分情况下不应该把错误转换为异常
        if (str_contains($errFile, '/phpunit/src/'))
        {
            $this->stopPropagation();

            $level = match ($errNo)
            {
                \E_ERROR, \E_PARSE, \E_CORE_ERROR, \E_COMPILE_ERROR, \E_USER_ERROR, \E_RECOVERABLE_ERROR => LogLevel::ERROR,
                \E_WARNING, \E_CORE_WARNING, \E_COMPILE_WARNING, \E_USER_WARNING => LogLevel::WARNING,
                \E_NOTICE, \E_USER_NOTICE => LogLevel::NOTICE,
                default => LogLevel::INFO,
            };
            Log::log($level, $errStr);
        }
    }

    public function handleException(\Throwable $throwable): void
    {
    }
}
