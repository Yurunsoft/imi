<?php

declare(strict_types=1);

namespace Imi\Test\Component\Tests\Cache;

use Imi\Bean\BeanFactory;
use Imi\Cache\Handler\Redis;
use Imi\Redis\Connector\IRedisConnector;
use Imi\Redis\Connector\PhpRedisConnector;
use Imi\Redis\Connector\PredisConnector;
use Imi\Redis\Connector\RedisDriverConfig;
use Imi\Redis\Enum\RedisMode;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\RedisConnectionService;
use Imi\Test\BaseTest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\SimpleCache\CacheInterface;

use function Imi\env;

class RedisCacheTest extends BaseTest
{
    private array $connections = [];
    private array $handlers = [];
    protected bool $supportTTL = true;

    public static function redisConnectionProvider(): array
    {
        return [
            'phpredis_base'    => ['phpredis_base'],
            'phpredis_cluster' => ['phpredis_cluster'],
            'predis_base'      => ['predis_base'],
            'predis_cluster'   => ['predis_cluster'],
        ];
    }

    public function getRedisConnection(string $name): RedisConnectionService
    {
        if (isset($this->connections[$name]))
        {
            return $this->connections[$name];
        }

        switch ($name)
        {
            case 'phpredis_base':
                assert_env_redis_is_ready();
                $config = new RedisDriverConfig(
                    client: 'phpredis',
                    mode: RedisMode::Standalone,
                    scheme: null,
                    host: env('REDIS_SERVER_HOST', '127.0.0.1'),
                    port: env('REDIS_SERVER_PORT', 6379),
                    seeds: null,
                    password: env('REDIS_SERVER_PASSWORD'),
                    database: 0,
                    prefix: '',
                    timeout: 1,
                    readTimeout: 1,
                    serialize: false,
                    options: [],
                    tls: null,
                );
                break;
            case 'phpredis_cluster':
                assert_env_redis_cluster_is_ready();
                $config = new RedisDriverConfig(
                    client: 'phpredis',
                    mode: RedisMode::Cluster,
                    scheme: null,
                    host: '127.0.0.1',
                    port: 0,
                    seeds: explode(',', env('REDIS_SERVER_CLUSTER_SEEDS', '')),
                    password: env('REDIS_SERVER_CLUSTER_PASSWORD'),
                    database: 0,
                    prefix: '',
                    timeout: 1,
                    readTimeout: 1,
                    serialize: false,
                    options: [],
                    tls: null,
                );
                break;
            case 'predis_base':
                assert_env_redis_is_ready();
                $config = new RedisDriverConfig(
                    client: 'predis',
                    mode: RedisMode::Standalone,
                    scheme: null,
                    host: env('REDIS_SERVER_HOST', '127.0.0.1'),
                    port: env('REDIS_SERVER_PORT', 6379),
                    seeds: null,
                    password: env('REDIS_SERVER_PASSWORD'),
                    database: 0,
                    prefix: '',
                    timeout: 1,
                    readTimeout: 1,
                    serialize: false,
                    options: [],
                    tls: null,
                );
                break;
            case 'predis_cluster':
                assert_env_redis_cluster_is_ready();
                $config = new RedisDriverConfig(
                    client: 'predis',
                    mode: RedisMode::Cluster,
                    scheme: null,
                    host: '127.0.0.1',
                    port: 0,
                    seeds: explode(',', env('REDIS_SERVER_CLUSTER_SEEDS', '')),
                    password: env('REDIS_SERVER_CLUSTER_PASSWORD'),
                    database: 0,
                    prefix: '',
                    timeout: 1,
                    readTimeout: 1,
                    serialize: false,
                    options: [],
                    tls: null,
                );
                break;
            default:
                throw new \RuntimeException("Unsupported redis connection: {$name}");
        }

        /** @var IRedisConnector $connector */
        $connector = match ($config->client)
        {
            'phpredis' => PhpRedisConnector::class,
            'predis'   => PredisConnector::class,
            default    => throw new \RuntimeException(sprintf('Unsupported redis client: %s', $config->client)),
        };
        $redisHandler = match ($config->mode)
        {
            RedisMode::Standalone => $connector::connect($config),
            RedisMode::Cluster    => $connector::connectCluster($config),
            RedisMode::Sentinel   => throw new \RuntimeException('To be implemented'),
        };
        $this->connections[$name] = new class($redisHandler) extends RedisConnectionService {
            public function __construct(
                private readonly IRedisHandler $redisHandler,
            ) {
                parent::__construct();
            }

            public function getInstance(): IRedisHandler
            {
                // @phpstan-ignore-next-line
                return $this->redisHandler;
            }
        };

        return $this->connections[$name];
    }

    public function getCacheHandler(string $name): CacheInterface
    {
        if (isset($this->handlers[$name]))
        {
            return $this->handlers[$name];
        }

        $connection = $this->getRedisConnection($name);

        $handler = BeanFactory::newInstance(Redis::class, [
            'poolName'   => null,
            'prefix'     => $connection->getInstance()->isCluster() ? '{imi-test}:' : '',
            'replaceDot' => false,
        ], $connection);
        $this->handlers[$name] = $handler;

        return $this->handlers[$name];
    }

    #[DataProvider('redisConnectionProvider')]
    public function testSetAndGet(string $name): void
    {
        $cache = $this->getCacheHandler($name);

        $key = 'testSetAndGet_' . bin2hex(random_bytes(8));
        $value = 'nb_' . bin2hex(random_bytes(8));

        Assert::assertTrue($cache->set($key, $value));
        Assert::assertEquals($value, $cache->get($key));
    }

    /**
     * @testdox Set TTL
     */
    #[DataProvider('redisConnectionProvider')]
    public function testSetTTL(string $name): void
    {
        if (!$this->supportTTL)
        {
            $this->markTestSkipped('Handler does not support TTL');
        }
        $cache = $this->getCacheHandler($name);

        $this->go(static function () use ($cache): void {
            $key = 'testSetTTL_' . bin2hex(random_bytes(8));
            $value = 'value_' . bin2hex(random_bytes(8));

            Assert::assertTrue($cache->set($key, $value, 1));
            Assert::assertEquals($value, $cache->get($key));
            sleep(2);
            Assert::assertEquals('none', $cache->get($key, 'none'));
        }, null, 3);
    }

    #[DataProvider('redisConnectionProvider')]
    public function testSetMultiple(string $name): void
    {
        if ('predis_cluster' === $name)
        {
            $this->expectExceptionMessage('predis cluster not support setMultiple method');
        }
        $value = bin2hex(random_bytes(8));

        $values = [
            'k1'       => 'v1' . $value,
            'k2'       => 'v2' . $value,
            '19940312' => 'yurun' . $value, // 数字键名测试
        ];
        $cache = $this->getCacheHandler($name);

        Assert::assertTrue($cache->setMultiple($values));
        $getValues = $cache->getMultiple(array_keys_string($values));
        Assert::assertEquals($values, $getValues);
    }

    /**
     * @testdox Set multiple TTL
     */
    #[DataProvider('redisConnectionProvider')]
    public function testSetMultipleTTL(string $name): void
    {
        if (!$this->supportTTL)
        {
            $this->markTestSkipped('Handler does not support TTL');
        }
        if ('predis_cluster' === $name)
        {
            $this->expectExceptionMessage('predis cluster not support setMultiple method');
        }
        $cache = $this->getCacheHandler($name);
        $this->go(static function () use ($cache): void {
            $value = bin2hex(random_bytes(8));

            $values = [
                'k1' => 'v1' . $value,
                'k2' => 'v2' . $value,
            ];
            Assert::assertTrue($cache->setMultiple($values, 1));
            $getValues = $cache->getMultiple(array_keys_string($values));
            Assert::assertEquals($values, $getValues);
            sleep(2);
            Assert::assertEquals([
                'k1' => 'none',
                'k2' => 'none',
            ], $cache->getMultiple(array_keys_string($values), 'none'));
        }, null, 3);
    }

    #[DataProvider('redisConnectionProvider')]
    public function testDelete(string $name): void
    {
        $key = 'testDelete_' . bin2hex(random_bytes(8));
        $value = 'value_' . bin2hex(random_bytes(8));

        $cache = $this->getCacheHandler($name);

        Assert::assertTrue($cache->set($key, $value));
        Assert::assertEquals($value, $cache->get($key));
        Assert::assertTrue($cache->delete($key));
        Assert::assertNull($cache->get($key));
    }

    #[DataProvider('redisConnectionProvider')]
    public function testDeleteMultiple(string $name): void
    {
        if ('predis_cluster' === $name)
        {
            $this->expectExceptionMessage('predis cluster not support setMultiple method');
        }
        $value = bin2hex(random_bytes(8));
        $values = [
            'k1'       => 'v1' . $value,
            'k2'       => 'v2' . $value,
            '19940312' => 'yurun' . $value, // 数字键名测试
        ];
        $cache = $this->getCacheHandler($name);
        Assert::assertTrue($cache->setMultiple($values));
        $getValues = $cache->getMultiple(array_keys_string($values));
        Assert::assertEquals($values, $getValues);

        Assert::assertTrue($cache->deleteMultiple(array_keys_string($values)));
        Assert::assertEquals([
            'k1'       => null,
            'k2'       => null,
            '19940312' => null,
        ], $cache->getMultiple(array_keys_string($values)));
    }

    #[DataProvider('redisConnectionProvider')]
    public function testHas(string $name): void
    {
        $cache = $this->getCacheHandler($name);

        $key = 'testHas_' . bin2hex(random_bytes(8));
        $value = 'value_' . bin2hex(random_bytes(8));

        Assert::assertTrue($cache->set($key, $value));
        Assert::assertTrue($cache->has($key));
        Assert::assertTrue($cache->delete($key));
        Assert::assertFalse($cache->has($key));
    }

    #[DataProvider('redisConnectionProvider')]
    public function testClear(string $name): void
    {
        if ('predis_cluster' === $name)
        {
            $this->expectExceptionMessage('predis cluster not support setMultiple method');
        }
        $value = 'value_' . bin2hex(random_bytes(8));

        $values = [
            'k1' => 'v1' . $value,
            'k2' => 'v2' . $value,
        ];

        $cache = $this->getCacheHandler($name);

        Assert::assertTrue($cache->setMultiple($values));
        $getValues = $cache->getMultiple(array_keys_string($values));
        Assert::assertEquals($values, $getValues);

        Assert::assertTrue($cache->clear());
        Assert::assertEquals([
            'k1' => null,
            'k2' => null,
        ], $cache->getMultiple(array_keys_string($values)));
    }
}
