<?php

declare(strict_types=1);

namespace Imi\Components\redis\tests\Tests;

use Imi\Redis\Connector\PhpRedisConnector;
use Imi\Redis\Connector\PredisConnector;
use Imi\Redis\Connector\RedisDriverConfig;
use Imi\Redis\Enum\RedisMode;
use Imi\Redis\Handler\IRedisClusterHandler;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisClusterHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;
use Monolog\Test\TestCase;

use function Imi\env;

class RedisHandlerTest extends TestCase
{
    public function testPhpRedisTlsConnection(): void
    {
        foreach ([
            'REDIS_SERVER_TLS_HOST',
            'REDIS_SERVER_TLS_PORT',
            'REDIS_SERVER_TLS_CA_FILE',
            'REDIS_SERVER_TLS_CERT_FILE',
            'REDIS_SERVER_TLS_KEY_FILE',
        ] as $key)
        {
            $value = env($key);
            if (empty($value))
            {
                self::markTestSkipped("tls options {$key} is empty, skip tls test");
            }
        }

        $config = new RedisDriverConfig(
            client: 'phpredis',
            mode: RedisMode::Standalone,
            scheme: null,
            host: env('REDIS_SERVER_TLS_HOST'),
            port: env('REDIS_SERVER_TLS_PORT'),
            seeds: null,
            password: env('REDIS_SERVER_TLS_PASSWORD'),
            database: 0,
            prefix: '',
            timeout: 1,
            readTimeout: 1,
            serialize: false,
            options: [],
            tls: [
                // https://www.php.net/context.ssl
                'verify_peer_name' => false,
                'cafile'           => env('REDIS_SERVER_TLS_CA_FILE'),
                'local_cert'       => env('REDIS_SERVER_TLS_CERT_FILE'),
                'local_pk'         => env('REDIS_SERVER_TLS_KEY_FILE'),
            ],
        );

        $handler = PhpRedisConnector::connect($config);

        self::assertHandlerSuccess($handler);
    }

    public function testPhpRedisUnixSockConnection(): void
    {
        if (\PHP_OS_FAMILY === 'Windows')
        {
            self::markTestSkipped('unixsock test not support windows');
        }

        if (!env('REDIS_SERVER_UNIX_SOCK'))
        {
            self::markTestSkipped('unixsock test not support, please set REDIS_SERVER_UNIX_SOCK');
        }

        $config = new RedisDriverConfig(
            client: 'phpredis',
            mode: RedisMode::Standalone,
            scheme: null,
            host: env('REDIS_SERVER_UNIX_SOCK'),
            port: 0,
            seeds: null,
            password: null,
            database: 0,
            prefix: '',
            timeout: 1,
            readTimeout: 1,
            serialize: false,
            options: [],
            tls: null,
        );

        $handler = PhpRedisConnector::connect($config);

        self::assertHandlerSuccess($handler);
    }

    public function testPhpRedisTlsClusterConnection(): void
    {
        foreach ([
            'REDIS_SERVER_TLS_CLUSTER_SEEDS',
            'REDIS_SERVER_TLS_CA_FILE',
            'REDIS_SERVER_TLS_CERT_FILE',
            'REDIS_SERVER_TLS_KEY_FILE',
        ] as $key)
        {
            $value = env($key);
            if (empty($value))
            {
                self::markTestSkipped("tls options {$key} is empty, skip tls test");
            }
        }

        $seeds = explode(',', env('REDIS_SERVER_TLS_CLUSTER_SEEDS', ''));

        $config = new RedisDriverConfig(
            client: 'phpredis',
            mode: RedisMode::Cluster,
            scheme: null,
            host: '0.0.0.0',
            port: 0,
            seeds: $seeds,
            password: env('REDIS_SERVER_TLS_PASSWORD'),
            database: 0,
            prefix: '',
            timeout: 1,
            readTimeout: 1,
            serialize: false,
            options: [],
            tls: [
                // https://www.php.net/context.ssl
                'verify_peer_name' => false,
                'cafile'           => env('REDIS_SERVER_TLS_CA_FILE'),
                'local_cert'       => env('REDIS_SERVER_TLS_CERT_FILE'),
                'local_pk'         => env('REDIS_SERVER_TLS_KEY_FILE'),
            ],
        );

        $handler = PhpRedisConnector::connectCluster($config);

        self::assertHandlerSuccess($handler);
    }

    public function testPredisTlsConnection(): void
    {
        foreach ([
            'REDIS_SERVER_TLS_HOST',
            'REDIS_SERVER_TLS_PORT',
            'REDIS_SERVER_TLS_CA_FILE',
            'REDIS_SERVER_TLS_CERT_FILE',
            'REDIS_SERVER_TLS_KEY_FILE',
        ] as $key)
        {
            $value = env($key);
            if (empty($value))
            {
                self::markTestSkipped("tls options {$key} is empty, skip tls test");
            }
        }

        $config = new RedisDriverConfig(
            client: 'predis',
            mode: RedisMode::Standalone,
            scheme: null,
            host: env('REDIS_SERVER_TLS_HOST'),
            port: env('REDIS_SERVER_TLS_PORT'),
            seeds: null,
            password: env('REDIS_SERVER_TLS_PASSWORD'),
            database: 0,
            prefix: '',
            timeout: 1,
            readTimeout: 1,
            serialize: false,
            options: [],
            tls: [
                // https://www.php.net/context.ssl
                'verify_peer_name' => false,
                'cafile'           => env('REDIS_SERVER_TLS_CA_FILE'),
                'local_cert'       => env('REDIS_SERVER_TLS_CERT_FILE'),
                'local_pk'         => env('REDIS_SERVER_TLS_KEY_FILE'),
            ],
        );

        $handler = PredisConnector::connect($config);

        self::assertHandlerSuccess($handler);
    }

    public function testPredisUnixSockConnection(): void
    {
        if (\PHP_OS_FAMILY === 'Windows')
        {
            self::markTestSkipped('unixsock test not support windows');
        }

        if (!env('REDIS_SERVER_UNIX_SOCK'))
        {
            self::markTestSkipped('unixsock test not support, please set REDIS_SERVER_UNIX_SOCK');
        }

        $config = new RedisDriverConfig(
            client: 'predis',
            mode: RedisMode::Standalone,
            scheme: 'unix',
            host: env('REDIS_SERVER_UNIX_SOCK'),
            port: 0,
            seeds: null,
            password: null,
            database: 0,
            prefix: '',
            timeout: 1,
            readTimeout: 1,
            serialize: false,
            options: [],
            tls: null,
        );

        $handler = PredisConnector::connect($config);

        self::assertHandlerSuccess($handler);
    }

    public function testPredisTlsClusterConnection(): void
    {
        foreach ([
            'REDIS_SERVER_TLS_CLUSTER_SEEDS',
            'REDIS_SERVER_TLS_CA_FILE',
            'REDIS_SERVER_TLS_CERT_FILE',
            'REDIS_SERVER_TLS_KEY_FILE',
        ] as $key)
        {
            $value = env($key);
            if (empty($value))
            {
                self::markTestSkipped("tls options {$key} is empty, skip tls test");
            }
        }

        $seeds = explode(',', env('REDIS_SERVER_TLS_CLUSTER_SEEDS', ''));

        $config = new RedisDriverConfig(
            client: 'predis',
            mode: RedisMode::Cluster,
            scheme: null,
            host: '0.0.0.0',
            port: 0,
            seeds: $seeds,
            password: env('REDIS_SERVER_TLS_PASSWORD'),
            database: 0,
            prefix: '',
            timeout: 1,
            readTimeout: 1,
            serialize: false,
            options: [],
            tls: [
                // https://www.php.net/context.ssl
                'verify_peer_name' => false,
                'cafile'           => env('REDIS_SERVER_TLS_CA_FILE'),
                'local_cert'       => env('REDIS_SERVER_TLS_CERT_FILE'),
                'local_pk'         => env('REDIS_SERVER_TLS_KEY_FILE'),
            ],
        );

        $handler = PredisConnector::connectCluster($config);

        self::assertHandlerSuccess($handler);
    }

    protected static function assertHandlerSuccess(IRedisHandler|IRedisClusterHandler|PhpRedisHandler|PhpRedisClusterHandler|PredisHandler|PredisClusterHandler $handler): void
    {
        self::assertTrue($handler->isConnected());
        $key = 'imi:test:set_' . bin2hex(random_bytes(8));
        self::assertTrue($handler->set($key, '123456'));
        self::assertEquals('123456', $handler->get($key));
        self::assertTrue($handler->del($key) > 0);
    }
}
