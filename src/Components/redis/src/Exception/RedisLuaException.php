<?php

declare(strict_types=1);

namespace Imi\Redis\Exception;

class RedisLuaException extends \LogicException
{
    public function __construct(
        readonly public string $className,
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("{$message} ({$className})", $code, $previous);
    }
}
