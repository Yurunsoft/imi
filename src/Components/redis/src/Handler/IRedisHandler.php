<?php

declare(strict_types=1);

namespace Imi\Redis\Handler;

interface IRedisHandler
{
    public function getInstance(): object;

    public function getClientVersion(): string;

    public function getServerVersion(): string;

    public function isConnected(): bool;

    public function isCluster(): bool;

    public function isSupportSerialize(): bool;

    public function flushdbEx(): bool;

    public function flushallEx(): bool;

    public function scanEach(?string $pattern = null, int $count = 0): \Generator;

    public function hscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator;

    public function sscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator;

    public function zscanEach(string $key, ?string $pattern = null, int $count = 0): \Generator;

    public function _serialize(mixed $value): ?string;

    public function _unserialize(?string $value): mixed;
}
