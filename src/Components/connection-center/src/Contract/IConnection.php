<?php

declare(strict_types=1);

namespace Imi\ConnectionCenter\Contract;

/**
 * 连接.
 */
use Imi\ConnectionCenter\Enum\ConnectionStatus;

/**
 * @template T of object
 */
interface IConnection
{
    /**
     * @param T $instance
     */
    public function __construct(IConnectionManager $manager, object $instance);

    /**
     * 获取连接所属管理器.
     */
    public function getManager(): IConnectionManager;

    /**
     * 获取连接资源管理的对象实例.
     *
     * @return T
     */
    public function getInstance(): object;

    /**
     * 释放连接，回归连接管理器.
     */
    public function release(): void;

    /**
     * 与连接管理器分离，作为一个独立连接.
     */
    public function detach(): void;

    /**
     * 获取状态
     */
    public function getStatus(): ConnectionStatus;
}
