<?php
declare(strict_types=1);

namespace Imi\Components\redis\tests\Tests;

use Imi\Redis\Connector\PhpRedisConnector;
use Imi\Redis\Connector\PredisConnector;
use Imi\Redis\Connector\RedisDriverConfig;
use Imi\Redis\Enum\RedisMode;
use Monolog\Test\TestCase;
use function Imi\env;

class RedisHandlerTest extends TestCase
{
    public function testPhpRedisUnixSockConnection()
    {
        if (\PHP_OS_FAMILY !== 'Linux')
        {
            self::markTestSkipped('unixsock test only support linux');
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
        );

        $handler = PhpRedisConnector::connect($config);

        self::assertTrue($handler->ping());
        $key = 'imi:test:set_' . \bin2hex(\random_bytes(8));
        self::assertTrue($handler->set($key, '123456'));
        self::assertEquals('123456', $handler->get($key));
        self::assertTrue($handler->del($key) > 0);
    }

    public function testPredisUnixSockConnection()
    {
        if (\PHP_OS_FAMILY !== 'Linux')
        {
            self::markTestSkipped('unixsock test only support linux');
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
        );

        $handler = PredisConnector::connect($config);

        self::assertEquals('PONG', (string) $handler->ping());
        $key = 'imi:test:set_' . \bin2hex(\random_bytes(8));
        self::assertEquals(true, $handler->set($key, '123456'));
        self::assertEquals('123456', $handler->get($key));
        self::assertTrue($handler->del($key) > 0);
    }
}
