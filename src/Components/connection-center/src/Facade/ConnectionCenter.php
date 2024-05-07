<?php

declare(strict_types=1);

namespace Imi\ConnectionCenter\Facade;

use Imi\Facade\BaseFacade;

/**
 * @method static \Imi\ConnectionCenter\Contract\IConnectionManager addConnectionManager(string $name, string $connectionManagerClass, mixed $config)
 * @method static void                                              removeConnectionManager(string $name)
 * @method static void                                              closeAllConnectionManager()
 * @method static bool                                              hasConnectionManager(string $name)
 * @method static \Imi\ConnectionCenter\Contract\IConnectionManager getConnectionManager(string $name)
 * @method static IConnectionManager[]                              getConnectionManagers()
 * @method static \Imi\ConnectionCenter\Contract\IConnection        getConnection(string $name)
 * @method static \Imi\ConnectionCenter\Contract\IConnection        getRequestContextConnection(string $name)
 * @method static mixed                                             createConnection(?string $name = NULL, bool $autoConnect = true)
 */
#[
    \Imi\Facade\Annotation\Facade(class: \Imi\ConnectionCenter\AppConnectionCenter::class)
]
class ConnectionCenter extends BaseFacade
{
}
