<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

interface IRedisScanExMethod
{
    public function scanEach(?string $pattern = null, int $count = 0);

    public function hscanEach(string $key, ?string $pattern = null, int $count = 0);

    public function sscanEach(string $key, ?string $pattern = null, int $count = 0);

    public function zscanEach(string $key, ?string $pattern = null, int $count = 0);
}
