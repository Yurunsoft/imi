<?php

declare(strict_types=1);

namespace Imi\Util;

/**
 * 支持键值过期的存储对象
 */
class ExpiredStorage
{
    /**
     * @var ExpiredStorageItem[]
     */
    protected array $data = [];

    public function __construct(array $data = [])
    {
        if ($data)
        {
            foreach ($data as $key => $value)
            {
                $this->data[$key] = new ExpiredStorageItem($value);
            }
        }
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value, float $ttl = 0): self
    {
        if (isset($this->data[$key]))
        {
            $item = $this->data[$key];
            $item->setValue($value);
            $item->setTTL($ttl);
        }
        else
        {
            $this->data[$key] = new ExpiredStorageItem($value, $ttl);
        }

        return $this;
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null, ?ExpiredStorageItem &$item = null)
    {
        if (isset($this->data[$key]))
        {
            $item = $this->data[$key];
            if (!$item->isExpired())
            {
                return $item->getValue();
            }
        }

        return $default;
    }

    public function unset(string $key): void
    {
        unset($this->data[$key]);
    }

    public function isset(string $key): bool
    {
        if (isset($this->data[$key]))
        {
            return !$this->data[$key]->isExpired();
        }

        return false;
    }

    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * @return ExpiredStorageItem[]
     */
    public function getItems(): array
    {
        return $this->data;
    }
}

final class ExpiredStorageItem
{
    /**
     * @var mixed
     */
    private $value;

    private float $ttl = 0;

    private float $lastModifyTime = 0;

    /**
     * @param mixed $value
     */
    public function __construct($value, float $ttl = 0)
    {
        $this->setValue($value);
        $this->setTTL($ttl);
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): self
    {
        $this->value = $value;
        $this->lastModifyTime = microtime(true);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setTTL(float $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function getTTL(): float
    {
        return $this->ttl;
    }

    public function isExpired(): bool
    {
        return $this->ttl > 0 && microtime(true) - $this->lastModifyTime > $this->ttl;
    }

    public function getLastModifyTime(): float
    {
        return $this->lastModifyTime;
    }
}
