<?php

declare(strict_types=1);

namespace Imi\Redis\Connector;

use Imi\ConnectionCenter\Contract\AbstractConnectionConfig;
use Imi\Redis\Enum\RedisMode;

class RedisDriverConfig extends AbstractConnectionConfig
{
    public function __construct(
        public readonly string $client,
        public readonly RedisMode $mode,
        public readonly ?string $scheme,
        public readonly string $host,
        public readonly ?int $port,
        public readonly ?array $seeds,
        public readonly ?string $password,
        public readonly int $database,
        public readonly string $prefix,
        public readonly float $timeout,
        public readonly float $readTimeout,
        public readonly bool $serialize,
        public readonly array $options,
        public readonly ?array $tls,
        float $weight = 0
    ) {
        parent::__construct(weight: $weight);
    }

    protected static function __create(array $config): self
    {
        return new self(
            client: $config['client'] ?? 'phpredis',
            mode: $config['mode'] ?? RedisMode::Standalone,
            scheme: $config['scheme'] ?? null,
            host: $config['host'] ?? '127.0.0.1',
            port: (int) ($config['port'] ?? 6379),
            seeds: $config['seeds'] ?? null,
            password: $config['password'] ?? null,
            database: $config['database'] ?? 0,
            prefix: $config['prefix'] ?? '',
            timeout: (float) ($config['timeout'] ?? 3.0),
            readTimeout: (float) ($config['readTimeout'] ?? 3.0),
            serialize: $config['serialize'] ?? true,
            options: $config['options'] ?? [],
            tls: $config['tls'] ?? null,
            weight: (int) ($config['weight'] ?? 0),
        );
    }
}
