<?php

declare(strict_types=1);

namespace Imi\Pool;

use Imi\Bean\Annotation\Bean;
use Imi\Pool\Interfaces\IPoolConfig;

/**
 * 池子配置.
 *
 * @Bean("PoolConfig")
 */
class PoolConfig implements IPoolConfig
{
    /**
     * 池子中最多资源数.
     */
    protected int $maxResources = 10;

    /**
     * 池子中最少资源数.
     */
    protected int $minResources = 1;

    /**
     * 资源回收时间间隔，单位：秒.
     */
    protected ?int $gcInterval = 60;

    /**
     * 资源最大存活时间，单位：秒.
     *
     * 为 null 则不限制
     */
    protected ?int $maxActiveTime = null;

    /**
     * 等待资源最大超时时间，单位：毫秒.
     */
    protected int $waitTimeout = 3000;

    /**
     * 每次获取资源最长使用时间，单位：秒.
     *
     * 为 null 则不限制
     */
    protected ?float $maxUsedTime = null;

    /**
     * 资源创建后最大空闲回收时间，单位：秒.
     */
    protected ?float $maxIdleTime = null;

    /**
     * 当前请求上下文资源检查状态间隔，单位：支持小数的秒.
     *
     * 为 null 则不限制
     */
    protected float $requestResourceCheckInterval = 30;

    /**
     * 心跳时间间隔，单位：秒.
     */
    protected ?float $heartbeatInterval = null;

    /**
     * 资源配置模式.
     */
    protected int $resourceConfigMode = ResourceConfigMode::TURN;

    /**
     * 当获取资源时，是否检查状态
     */
    protected bool $checkStateWhenGetResource = true;

    public function __construct(array $option = [])
    {
        if ($option)
        {
            foreach ($option as $k => $v)
            {
                $this->$k = $v;
            }
        }
    }

    /**
     * 池子中最多资源数.
     */
    public function getMaxResources(): int
    {
        return $this->maxResources;
    }

    /**
     * 池子中最少资源数.
     */
    public function getMinResources(): int
    {
        return $this->minResources;
    }

    /**
     * 获取资源回收时间间隔，单位：秒.
     */
    public function getGCInterval(): ?int
    {
        return $this->gcInterval;
    }

    /**
     * 获取资源最大存活时间，单位：秒.
     */
    public function getMaxActiveTime(): ?int
    {
        return $this->maxActiveTime;
    }

    /**
     * 获取等待资源最大超时时间，单位：毫秒.
     */
    public function getWaitTimeout(): int
    {
        return $this->waitTimeout;
    }

    /**
     * Set 池子中最多资源数.
     *
     * @param int $maxResources 池子中最多资源数
     *
     * @return static
     */
    public function setMaxResources(int $maxResources): self
    {
        $this->maxResources = $maxResources;

        return $this;
    }

    /**
     * Set 池子中最少资源数.
     *
     * @param int $minResources 池子中最少资源数
     *
     * @return static
     */
    public function setMinResources(int $minResources): self
    {
        $this->minResources = $minResources;

        return $this;
    }

    /**
     * Set 资源回收时间间隔，单位：秒.
     *
     * @param int|null $gcInterval 获取资源回收时间间隔，单位：秒
     *
     * @return static
     */
    public function setGcInterval(?int $gcInterval): self
    {
        $this->gcInterval = $gcInterval;

        return $this;
    }

    /**
     * Set 资源最大存活时间，单位：秒.
     *
     * @param int|null $maxActiveTime 获取资源最大存活时间，单位：秒
     *
     * @return static
     */
    public function setMaxActiveTime(?int $maxActiveTime): self
    {
        $this->maxActiveTime = $maxActiveTime;

        return $this;
    }

    /**
     * Set 等待资源最大超时时间.
     *
     * @param int $waitTimeout 等待资源最大超时时间
     *
     * @return static
     */
    public function setWaitTimeout(int $waitTimeout): self
    {
        $this->waitTimeout = $waitTimeout;

        return $this;
    }

    /**
     * 获取资源配置模式.
     */
    public function getResourceConfigMode(): int
    {
        return $this->resourceConfigMode;
    }

    /**
     * 设置资源配置模式.
     *
     * @return static
     */
    public function setResourceConfigMode(int $resourceConfigMode): self
    {
        $this->resourceConfigMode = $resourceConfigMode;

        return $this;
    }

    /**
     * Get 每次获取资源最长使用时间.
     */
    public function getMaxUsedTime(): ?float
    {
        return $this->maxUsedTime;
    }

    /**
     * Set 每次获取资源最长使用时间.
     *
     * @param float|null $maxUsedTime 为 null 则不限制
     *
     * @return static
     */
    public function setMaxUsedTime(?float $maxUsedTime): self
    {
        $this->maxUsedTime = $maxUsedTime;

        return $this;
    }

    /**
     * Get 资源创建后最大空闲回收时间.
     */
    public function getMaxIdleTime(): ?float
    {
        return $this->maxIdleTime;
    }

    /**
     * Set 资源创建后最大空闲回收时间.
     *
     * @return static
     */
    public function setMaxIdleTime(?float $maxIdleTime): self
    {
        $this->maxIdleTime = $maxIdleTime;

        return $this;
    }

    /**
     * 获取当前请求上下文资源检查状态间隔，单位：支持小数的秒.
     */
    public function getRequestResourceCheckInterval(): float
    {
        return $this->requestResourceCheckInterval;
    }

    /**
     * 设置当前请求上下文资源检查状态间隔，单位：支持小数的秒.
     *
     * @return static
     */
    public function setRequestResourceCheckInterval(float $value): self
    {
        $this->requestResourceCheckInterval = $value;

        return $this;
    }

    /**
     * 获取心跳时间间隔，单位：秒.
     */
    public function getHeartbeatInterval(): ?float
    {
        return $this->heartbeatInterval;
    }

    /**
     * Set 心跳时间间隔，单位：秒.
     *
     * @param float|null $heartbeatInterval 心跳时间间隔，单位：秒
     *
     * @return static
     */
    public function setHeartbeatInterval(?float $heartbeatInterval): self
    {
        $this->heartbeatInterval = $heartbeatInterval;

        return $this;
    }

    /**
     * 当获取资源时，是否检查状态
     */
    public function isCheckStateWhenGetResource(): bool
    {
        return $this->checkStateWhenGetResource;
    }

    /**
     * 设置获取资源时，是否检查状态
     *
     * @return static
     */
    public function setCheckStateWhenGetResource(bool $checkStateWhenGetResource): self
    {
        $this->checkStateWhenGetResource = $checkStateWhenGetResource;

        return $this;
    }
}
