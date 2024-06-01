<?php

declare(strict_types=1);

namespace Imi\ConnectionCenter\Test\Tests;

use Imi\App;
use Imi\ConnectionCenter\ConnectionCenter;
use Imi\ConnectionCenter\Enum\ConnectionStatus;
use Imi\ConnectionCenter\Handler\AlwaysNew\AlwaysNewConnectionManager;
use Imi\ConnectionCenter\Handler\Pool\PoolConnectionManager;
use Imi\ConnectionCenter\Handler\RequestContextSingleton\RequestContextSingletonConnectionManager;
use Imi\ConnectionCenter\Handler\Singleton\SingletonConnectionManager;
use Imi\ConnectionCenter\Test\Driver\TestDriver;
use Imi\RequestContext;
use PHPUnit\Framework\TestCase;

use function Imi\env;

class ConnectionCenterTest extends TestCase
{
    protected static ConnectionCenter $connectionCenter;

    protected static array $names = [];

    public function testNewInstance(): void
    {
        self::$connectionCenter = App::newInstance(ConnectionCenter::class);
        $names = [
            'alwaysNew',
            'requestContextSingleton',
            'singleton',
        ];
        if ('swoole' === env('CONNECTION_CENTER_TEST_MODE'))
        {
            $names[] = 'pool';
        }
        self::$names = $names;
        $this->assertTrue(true);
    }

    /**
     * @depends testNewInstance
     */
    public function testAddConnectionManager(): void
    {
        self::$connectionCenter->addConnectionManager('alwaysNew', AlwaysNewConnectionManager::class, AlwaysNewConnectionManager::createConfig(['driver' => TestDriver::class, 'enableStatistics' => true, 'resources' => [['test' => true]], 'checkStateWhenGetResource' => true, 'requestResourceCheckInterval' => 0]));

        // 测试连接管理器配置传入数组
        self::$connectionCenter->addConnectionManager('requestContextSingleton', RequestContextSingletonConnectionManager::class, ['driver' => TestDriver::class, 'enableStatistics' => true, 'resources' => [['test' => true]], 'checkStateWhenGetResource' => true, 'requestResourceCheckInterval' => 0]);

        self::$connectionCenter->addConnectionManager('singleton', SingletonConnectionManager::class, SingletonConnectionManager::createConfig(['driver' => TestDriver::class, 'enableStatistics' => true, 'resources' => [['test' => true]], 'checkStateWhenGetResource' => true, 'requestResourceCheckInterval' => 0]));

        if ('swoole' === env('CONNECTION_CENTER_TEST_MODE'))
        {
            self::$connectionCenter->addConnectionManager('pool', PoolConnectionManager::class, PoolConnectionManager::createConfig(['driver' => TestDriver::class, 'enableStatistics' => true, 'resources' => [['test' => true]], 'checkStateWhenGetResource' => true, 'requestResourceCheckInterval' => 0]));
        }

        $this->expectExceptionMessage('Connection manager alwaysNew already exists');
        self::$connectionCenter->addConnectionManager('alwaysNew', AlwaysNewConnectionManager::class, AlwaysNewConnectionManager::createConfig(['driver' => TestDriver::class, 'enableStatistics' => true, 'resources' => [['test' => true]], 'checkStateWhenGetResource' => true, 'requestResourceCheckInterval' => 0]));
    }

    /**
     * @depends testAddConnectionManager
     */
    public function testGetConnectionManagers(): void
    {
        $this->assertEquals('swoole' === env('CONNECTION_CENTER_TEST_MODE') ? 4 : 3, \count(self::$connectionCenter->getConnectionManagers()));
    }

    /**
     * @depends testAddConnectionManager
     */
    public function testHasConnectionManager(): void
    {
        $this->assertTrue(self::$connectionCenter->hasConnectionManager('alwaysNew'));
        $this->assertTrue(self::$connectionCenter->hasConnectionManager('requestContextSingleton'));
        $this->assertTrue(self::$connectionCenter->hasConnectionManager('singleton'));
        if ('swoole' === env('CONNECTION_CENTER_TEST_MODE'))
        {
            $this->assertTrue(self::$connectionCenter->hasConnectionManager('pool'));
        }

        $this->assertFalse(self::$connectionCenter->hasConnectionManager('notFound'));
    }

    /**
     * @depends testAddConnectionManager
     */
    public function testRemoveConnectionManager(): void
    {
        self::$connectionCenter->addConnectionManager('test', AlwaysNewConnectionManager::class, AlwaysNewConnectionManager::createConfig(['driver' => TestDriver::class, 'enableStatistics' => true, 'resources' => [['test' => true]]]));
        $this->assertTrue(self::$connectionCenter->hasConnectionManager('test'));
        self::$connectionCenter->removeConnectionManager('test');
        $this->assertFalse(self::$connectionCenter->hasConnectionManager('test'));

        $this->expectExceptionMessage('Connection manager test does not exists');
        self::$connectionCenter->removeConnectionManager('test');
    }

    /**
     * @depends testAddConnectionManager
     */
    public function testGetConnectionManager(): void
    {
        $this->assertInstanceOf(AlwaysNewConnectionManager::class, self::$connectionCenter->getConnectionManager('alwaysNew'));

        $this->assertInstanceOf(RequestContextSingletonConnectionManager::class, self::$connectionCenter->getConnectionManager('requestContextSingleton'));

        $this->assertInstanceOf(SingletonConnectionManager::class, self::$connectionCenter->getConnectionManager('singleton'));

        if ('swoole' === env('CONNECTION_CENTER_TEST_MODE'))
        {
            $this->assertInstanceOf(PoolConnectionManager::class, self::$connectionCenter->getConnectionManager('pool'));
        }
    }

    /**
     * @depends testAddConnectionManager
     */
    public function testGetConnection(): void
    {
        foreach (self::$names as $name)
        {
            $connection = self::$connectionCenter->getConnection($name);
            $this->assertEquals(ConnectionStatus::Available, $connection->getStatus());
        }
    }

    /**
     * @depends testAddConnectionManager
     */
    public function testGetRequestContextConnection(): void
    {
        foreach (self::$names as $name)
        {
            $connection1 = self::$connectionCenter->getRequestContextConnection($name);
            $this->assertEquals(ConnectionStatus::Available, $connection1->getStatus());
            $connection2 = self::$connectionCenter->getRequestContextConnection($name);
            $this->assertEquals(ConnectionStatus::Available, $connection2->getStatus());
            $this->assertTrue($connection1 === $connection2);
        }
    }

    /**
     * 连接获取过期测试.
     */
    public function testCheckStateWhenGetResource(): void
    {
        foreach (self::$names as $name)
        {
            $connection1 = self::$connectionCenter->getRequestContextConnection($name);
            $this->assertEquals(ConnectionStatus::Available, $connection1->getStatus());

            $connection1->getInstance()->connected = false;

            $connection2 = self::$connectionCenter->getRequestContextConnection($name);
            $this->assertEquals(ConnectionStatus::Available, $connection2->getStatus());

            $this->assertTrue($connection1 !== $connection2);
        }
    }

    /**
     * 连接上下文销毁释放测试.
     */
    public function testRequestContextDestroy(): void
    {
        // 请求上下文单例
        if (RequestContext::exists(RequestContext::getCurrentId()))
        {
            RequestContext::destroy();
            RequestContext::create();
        }
        $manager = self::$connectionCenter->getConnectionManager('requestContextSingleton');
        $connection = self::$connectionCenter->getRequestContextConnection('requestContextSingleton');
        $this->assertEquals(1, $manager->getStatistics()->getTotalConnectionCount());
        RequestContext::destroy();
        RequestContext::create();
        $this->assertEquals(ConnectionStatus::Unavailable, $connection->getStatus());
        $this->assertEquals(0, $manager->getStatistics()->getTotalConnectionCount());

        // 连接池
        if ('swoole' === env('CONNECTION_CENTER_TEST_MODE'))
        {
            if (RequestContext::exists(RequestContext::getCurrentId()))
            {
                RequestContext::destroy();
                RequestContext::create();
            }
            $manager = self::$connectionCenter->getConnectionManager('pool');
            $connection = self::$connectionCenter->getRequestContextConnection('pool');
            $this->assertEquals(0, $manager->getStatistics()->getFreeConnectionCount());
            RequestContext::destroy();
            RequestContext::create();
            $this->assertEquals(ConnectionStatus::Unavailable, $connection->getStatus());
            $this->assertEquals(1, $manager->getStatistics()->getFreeConnectionCount());
        }
    }

    public function testCreateConnection(): void
    {
        foreach (self::$names as $name)
        {
            $connection1 = self::$connectionCenter->createConnection($name);
            $this->assertTrue($connection1->connected);

            $connection2 = self::$connectionCenter->createConnection($name, false);
            $this->assertFalse($connection2->connected);

            $this->assertTrue(self::$connectionCenter->createConnection($name) !== self::$connectionCenter->createConnection($name));
        }
    }

    /**
     * @depends testNewInstance
     */
    public function testCloseAllConnectionManager(): void
    {
        self::$connectionCenter->closeAllConnectionManager();
        $this->assertTrue(true);
    }
}
