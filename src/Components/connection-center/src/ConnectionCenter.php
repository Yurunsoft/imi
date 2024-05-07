<?php

declare(strict_types=1);

namespace Imi\ConnectionCenter;

use Imi\App;
use Imi\ConnectionCenter\Contract\IConnection;
use Imi\ConnectionCenter\Contract\IConnectionManager;
use Imi\ConnectionCenter\Contract\IConnectionManagerConfig;
use Imi\ConnectionCenter\Enum\ConnectionStatus;
use Imi\RequestContext;

class ConnectionCenter
{
    /**
     * @var IConnectionManager[]
     */
    protected array $connectionManagers = [];

    public function addConnectionManager(string $name, string $connectionManagerClass, mixed $config): IConnectionManager
    {
        if (isset($this->connectionManagers[$name]))
        {
            throw new \RuntimeException(sprintf('Connection manager %s already exists', $name));
        }

        if (!$config instanceof IConnectionManagerConfig)
        {
            $config = $connectionManagerClass::createConfig($config);
        }

        return $this->connectionManagers[$name] = App::newInstance($connectionManagerClass, $config);
    }

    public function removeConnectionManager(string $name): void
    {
        $connectionManager = $this->getConnectionManager($name);
        $connectionManager->close();
        unset($this->connectionManagers[$name]);
    }

    public function closeAllConnectionManager(): void
    {
        foreach ($this->connectionManagers as $manager)
        {
            $manager->close();
        }
        $this->connectionManagers = [];
    }

    public function hasConnectionManager(string $name): bool
    {
        return isset($this->connectionManagers[$name]);
    }

    public function getConnectionManager(string $name): IConnectionManager
    {
        if (isset($this->connectionManagers[$name]))
        {
            return $this->connectionManagers[$name];
        }
        else
        {
            throw new \RuntimeException(sprintf('Connection manager %s does not exists', $name));
        }
    }

    /**
     * @return IConnectionManager[]
     */
    public function getConnectionManagers(): array
    {
        return $this->connectionManagers;
    }

    /**
     * 获取连接.
     *
     * 需要调用 getInstance() 获取实际的对象进行操作.
     *
     * 需要自行管理连接生命周期，连接对象释放即归还连接池.
     */
    public function getConnection(string $name): IConnection
    {
        return $this->getConnectionManager($name)->getConnection();
    }

    /**
     * 获取连接.
     *
     * 自动管理生命周期，当前上下文结束时自动释放.
     */
    public function getRequestContextConnection(string $name): IConnection
    {
        $requestContext = RequestContext::getContext();
        /** @var IConnection|null $connection */
        $connection = $requestContext[static::class][$name]['connection'] ?? null;
        if (null !== $connection)
        {
            $manager = $connection->getManager();
            $managerConfig = $manager->getConfig();
            if ($managerConfig->isCheckStateWhenGetResource())
            {
                $requestResourceCheckInterval = $manager->getConfig()->getRequestResourceCheckInterval();
                if (($requestResourceCheckInterval <= 0 || (microtime(true) - $requestContext[static::class][$name]['lastGetTime'] > $requestResourceCheckInterval)) && !$manager->getDriver()->checkAvailable($connection->getInstance()))
                {
                    $connection->release();
                    $connection = null;
                }
            }
        }
        if (null === $connection || ConnectionStatus::Available !== $connection->getStatus())
        {
            $connection = $this->getConnection($name);
            $requestContext[static::class][$name] = [
                'connection'  => $connection,
            ];
            $connectionRef = \WeakReference::create($connection);
            $requestContext->defer(static function () use ($connectionRef): void {
                if (($connection = $connectionRef->get()) && ConnectionStatus::Available === $connection->getStatus())
                {
                    $connection->release();
                }
            });
        }
        if (($managerConfig ?? $connection->getManager()->getConfig())->isCheckStateWhenGetResource())
        {
            $requestContext[static::class][$name]['lastGetTime'] = microtime(true);
        }

        return $connection;
    }

    /**
     * 创建新的连接.
     *
     * 返回的连接是实际的对象，不受连接管理器管理生命周期.
     */
    public function createConnection(?string $name = null, bool $autoConnect = true): mixed
    {
        $driver = self::getConnectionManager($name)->getDriver();
        $instance = $driver->createInstance();

        if ($autoConnect)
        {
            return $driver->connect($instance);
        }

        return $instance;
    }
}
