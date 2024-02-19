<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

interface IRedisScanMethod
{
    public function scanEach(?string $pattern = null, int $count = 0): \Generator;

    public function hscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator;

    public function sscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator;

    public function zscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator;
}
