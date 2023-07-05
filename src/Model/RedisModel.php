<?php

declare(strict_types=1);

namespace Imi\Model;

use Imi\App;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\BeanFactory;
use Imi\Model\Annotation\RedisEntity;
use Imi\Model\Enum\RedisStorageMode;
use Imi\Model\Key\KeyRule;
use Imi\Redis\RedisHandler;
use Imi\Redis\RedisManager;
use Imi\Util\Format\IFormat;

/**
 * Redis 模型.
 */
abstract class RedisModel extends BaseModel
{
    /**
     * 键规则缓存.
     */
    protected static array $keyRules = [];

    /**
     * member规则缓存.
     */
    protected static array $memberRules = [];

    /**
     * 默认的key.
     */
    protected string $key = '';

    /**
     * 默认的member.
     */
    protected string $__member = '';

    /**
     * set时，设置的数据过期时间.
     */
    protected ?int $__ttl = null;

    /**
     * @param string|object|null $object
     */
    public static function __getRedisEntity($object = null): ?RedisEntity
    {
        if (null === $object)
        {
            $object = static::__getRealClassName();
        }
        else
        {
            $object = BeanFactory::getObjectClass($object);
        }

        // @phpstan-ignore-next-line
        return AnnotationManager::getClassAnnotations($object, RedisEntity::class, true, true);
    }

    public function __init(array $data = []): void
    {
        parent::__init($data);
        $this->__ttl = static::__getRedisEntity($this)->ttl;
    }

    /**
     * 查找一条记录.
     *
     * @param string|array $condition
     *
     * @return static|null
     */
    public static function find($condition): ?self
    {
        /** @var \Imi\Model\Annotation\RedisEntity $redisEntity */
        $redisEntity = static::__getRedisEntity(static::__getRealClassName());
        $key = static::generateKey($condition);
        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                $data = static::__getRedis()->get($key);
                if ($data && null !== $redisEntity->formatter)
                {
                    /** @var IFormat $formatter */
                    $formatter = App::getBean($redisEntity->formatter);
                    $data = $formatter->decode($data);
                }
                break;
            case RedisStorageMode::HASH:
                $member = static::generateMember($condition);
                $data = static::__getRedis()->hGet($key, $member);
                if ($data && null !== $redisEntity->formatter)
                {
                    /** @var IFormat $formatter */
                    $formatter = App::getBean($redisEntity->formatter);
                    $data = $formatter->decode($data);
                }
                break;
            case RedisStorageMode::HASH_OBJECT:
                $data = static::__getRedis()->hGetAll($key);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid RedisEntity->storage %s', $redisEntity->storage));
        }
        if (!$data)
        {
            return null;
        }
        $record = static::createFromRecord($data);
        $record->key = $key;
        if (isset($member))
        {
            $record->__member = $member;
        }

        return $record;
    }

    /**
     * 查询多条记录.
     *
     * @param mixed $conditions
     *
     * @return static[]
     */
    public static function select(...$conditions): array
    {
        /** @var \Imi\Model\Annotation\RedisEntity $redisEntity */
        $redisEntity = static::__getRedisEntity(static::__getRealClassName());
        if (null !== $redisEntity->formatter)
        {
            /** @var IFormat $formatter */
            $formatter = App::getBean($redisEntity->formatter);
        }
        $keys = [];
        if ($conditions)
        {
            foreach ($conditions as $condition)
            {
                $keys[] = static::generateKey($condition);
            }
        }
        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                $datas = static::__getRedis()->mget($keys);
                $list = [];
                if ($datas)
                {
                    foreach ($datas as $i => $data)
                    {
                        if (null !== $data && false !== $data)
                        {
                            if (isset($formatter))
                            {
                                $data = $formatter->decode($data);
                            }
                            $record = static::createFromRecord($data);
                            $record->key = $keys[$i];
                            $list[] = $record;
                        }
                    }
                }

                return $list;
            case RedisStorageMode::HASH:
                $members = [];
                if ($conditions)
                {
                    foreach ($conditions as $condition)
                    {
                        $members[] = static::generateMember($condition);
                    }
                }
                $list = [];
                $redis = static::__getRedis();
                if ($keys)
                {
                    foreach (array_unique($keys) as $key)
                    {
                        $datas = $redis->hMget($key, $members);
                        if ($datas)
                        {
                            foreach ($datas as $i => $data)
                            {
                                if (null !== $data)
                                {
                                    if (isset($formatter))
                                    {
                                        $data = $formatter->decode($data);
                                    }
                                    $record = static::createFromRecord($data);
                                    $record->key = $key;
                                    if (isset($members[$i]))
                                    {
                                        $record->__member = $members[$i];
                                    }
                                    $list[] = $record;
                                }
                            }
                        }
                    }
                }

                return $list;
            case RedisStorageMode::HASH_OBJECT:
                $redis = static::__getRedis();
                $list = [];
                if ($keys)
                {
                    foreach ($keys as $key)
                    {
                        $data = $redis->hGetAll($key);
                        $record = static::createFromRecord($data);
                        $record->key = $key;
                        $list[] = $record;
                    }
                }

                return $list;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid RedisEntity->storage %s', $redisEntity->storage));
        }
    }

    /**
     * 保存记录.
     */
    public function save(): bool
    {
        /** @var \Imi\Model\Annotation\RedisEntity $redisEntity */
        $redisEntity = static::__getRedisEntity(static::__getRealClassName());
        $redis = static::__getRedis($this);
        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                if (null === $redisEntity->formatter)
                {
                    $data = $this->toArray();
                }
                else
                {
                    /** @var IFormat $formatter */
                    $formatter = App::getBean($redisEntity->formatter);
                    $data = $formatter->encode($this->toArray());
                }
                if (null === $this->__ttl)
                {
                    return $redis->set($this->__getKey(), $data);
                }
                else
                {
                    return $redis->set($this->__getKey(), $data, $this->__ttl);
                }
                // no break
            case RedisStorageMode::HASH:
                if (null === $redisEntity->formatter)
                {
                    $data = $this->toArray();
                }
                else
                {
                    /** @var IFormat $formatter */
                    $formatter = App::getBean($redisEntity->formatter);
                    $data = $formatter->encode($this->toArray());
                }

                return false !== $redis->hSet($this->__getKey(), $this->__getMember(), $data);
            case RedisStorageMode::HASH_OBJECT:
                $key = $this->__getKey();
                $result = $redis->hMset($key, $this->toArray());
                if ($result && null !== $this->__ttl)
                {
                    $result = $redis->expire($key, $this->__ttl);
                }

                return $result;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid RedisEntity->storage %s', $redisEntity->storage));
        }
    }

    /**
     * 删除记录.
     */
    public function delete(): bool
    {
        /** @var \Imi\Model\Annotation\RedisEntity $redisEntity */
        $redisEntity = static::__getRedisEntity(static::__getRealClassName());
        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                return static::__getRedis($this)->del($this->__getKey()) > 0;
            case RedisStorageMode::HASH:
                return static::__getRedis($this)->hDel($this->__getKey(), $this->__getMember()) > 0;
            case RedisStorageMode::HASH_OBJECT:
                return static::__getRedis($this)->del($this->__getKey()) > 0;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid RedisEntity->storage %s', $redisEntity->storage));
        }
    }

    /**
     * 安全删除记录.
     *
     * 如果值发生改变，则不删除.
     */
    public function safeDelete(): bool
    {
        /** @var \Imi\Model\Annotation\RedisEntity $redisEntity */
        $redisEntity = static::__getRedisEntity(static::__getRealClassName());
        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                if (null === $redisEntity->formatter)
                {
                    $data = $this->toArray();
                }
                else
                {
                    /** @var IFormat $formatter */
                    $formatter = App::getBean($redisEntity->formatter);
                    $data = $formatter->encode($this->toArray());
                }
                $redis = static::__getRedis($this);
                $data = $redis->_serialize($data);

                return (bool) $redis->evalEx(<<<'LUA'
                if (ARGV[1] == redis.call('get', KEYS[1])) then
                    return redis.call('del', KEYS[1])
                else
                    return 0
                end
                LUA, [$this->__getKey(), $data], 1);
            case RedisStorageMode::HASH:
                if (null === $redisEntity->formatter)
                {
                    $data = $this->toArray();
                }
                else
                {
                    /** @var IFormat $formatter */
                    $formatter = App::getBean($redisEntity->formatter);
                    $data = $formatter->encode($this->toArray());
                }
                $redis = static::__getRedis($this);
                $data = $redis->_serialize($data);

                return (bool) $redis->evalEx(<<<'LUA'
                if (ARGV[1] == redis.call('hget', KEYS[1], ARGV[1])) then
                    return redis.call('hdel', KEYS[1], ARGV[1])
                else
                    return 0
                end
                LUA, [$this->__getKey(), $this->__getMember(), $data], 1);
            case RedisStorageMode::HASH_OBJECT:
                $data = $this->toArray();
                $argv = [];
                $redis = static::__getRedis($this);
                foreach ($data as $key => $value)
                {
                    $argv[] = $key;
                    $argv[] = $redis->_serialize($value);
                }

                return (bool) $redis->evalEx(<<<'LUA'
                local data = redis.call('hgetall', KEYS[1])
                for i = 1, #data do
                    if (ARGV[i] ~= data[i]) then
                        return 0
                    end
                end
                return redis.call('del', KEYS[1])
                LUA, [$this->__getKey(), ...$argv], 1);
            default:
                throw new \InvalidArgumentException(sprintf('Invalid RedisEntity->storage %s', $redisEntity->storage));
        }
    }

    /**
     * 批量删除.
     *
     * @param mixed ...$conditions
     */
    public static function deleteBatch(...$conditions): int
    {
        if (!$conditions)
        {
            return 0;
        }
        /** @var \Imi\Model\Annotation\RedisEntity $redisEntity */
        $redisEntity = static::__getRedisEntity(static::__getRealClassName());
        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                $keys = [];
                foreach ($conditions as $condition)
                {
                    $keys[] = static::generateKey($condition);
                }

                return static::__getRedis()->del(...$keys) ?: 0;
            case RedisStorageMode::HASH:
                $result = 0;
                foreach ($conditions as $condition)
                {
                    $key = static::generateKey($condition);
                    $member = static::generateMember($condition);
                    $result += (static::__getRedis()->hDel($key, $member) ?: 0);
                }

                return $result;
            case RedisStorageMode::HASH_OBJECT:
                $keys = [];
                foreach ($conditions as $condition)
                {
                    $keys[] = static::generateKey($condition);
                }

                return static::__getRedis()->del(...$keys) ?: 0;
            default:
                throw new \InvalidArgumentException(sprintf('Invalid RedisEntity->storage %s', $redisEntity->storage));
        }
    }

    /**
     * 获取键.
     */
    public function __getKey(): string
    {
        $rule = static::__getKeyRule($this);
        $replaces = [];
        foreach ($rule->paramNames as $paramName)
        {
            if (!isset($this[$paramName]))
            {
                throw new \RuntimeException(sprintf('__getKey param %s does not exists', $paramName));
            }
            $replaces['{' . $paramName . '}'] = $this[$paramName];
        }

        return strtr($rule->rule, $replaces);
    }

    /**
     * 生成key.
     *
     * @param string|array $condition
     */
    public static function generateKey($condition): string
    {
        if (\is_string($condition))
        {
            return $condition;
        }
        else
        {
            $rule = static::__getKeyRule();
            $replaces = [];
            foreach ($rule->paramNames as $paramName)
            {
                if (!isset($condition[$paramName]))
                {
                    throw new \RuntimeException(sprintf('GenerateKey param %s does not exists', $paramName));
                }
                $replaces['{' . $paramName . '}'] = $condition[$paramName];
            }

            return strtr($rule->rule, $replaces);
        }
    }

    /**
     * 生成member.
     *
     * @param string|array $condition
     */
    public static function generateMember($condition): string
    {
        if (\is_string($condition))
        {
            return $condition;
        }
        else
        {
            $rule = static::__getMemberRule();
            $replaces = [];
            foreach ($rule->paramNames as $paramName)
            {
                if (!isset($condition[$paramName]))
                {
                    throw new \RuntimeException(sprintf('GenerateMember param %s does not exists', $paramName));
                }
                $replaces['{' . $paramName . '}'] = $condition[$paramName];
            }

            return strtr($rule->rule, $replaces);
        }
    }

    /**
     * 获取键.
     */
    public function __getMember(): string
    {
        $rule = static::__getMemberRule($this);
        $replaces = [];
        foreach ($rule->paramNames as $paramName)
        {
            if (!isset($this[$paramName]))
            {
                throw new \RuntimeException(sprintf('__getMember param %s does not exists', $paramName));
            }
            $replaces['{' . $paramName . '}'] = $this[$paramName];
        }

        return strtr($rule->rule, $replaces);
    }

    /**
     * 获取Redis操作对象
     *
     * @param static|null $redisModel
     */
    public static function __getRedis(?self $redisModel = null): RedisHandler
    {
        $annotation = static::__getRedisEntity($redisModel ?? static::class);
        $redis = RedisManager::getInstance($annotation->poolName);
        if (null !== $annotation->db)
        {
            $redis->select($annotation->db);
        }

        return $redis;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get 默认的member.
     */
    public function getMember(): string
    {
        return $this->__member;
    }

    /**
     * Set 默认的member.
     *
     * @param string $member 默认的member
     */
    public function setMember(string $member): self
    {
        $this->__member = $member;

        return $this;
    }

    /**
     * 获取键.
     *
     * @param string|object|null $object
     */
    public static function __getKeyRule($object = null): KeyRule
    {
        if (null === $object)
        {
            $class = static::__getRealClassName();
        }
        else
        {
            $class = BeanFactory::getObjectClass($object);
        }
        $staticKeyRules = &self::$keyRules;
        if (isset($staticKeyRules[$class]))
        {
            return $staticKeyRules[$class];
        }
        else
        {
            /** @var RedisEntity|null $redisEntity */
            $redisEntity = AnnotationManager::getClassAnnotations($class, RedisEntity::class, true, true);
            $key = $redisEntity ? $redisEntity->key : '';
            preg_match_all('/{([^}]+)}/', $key, $matches);

            return $staticKeyRules[$class] = new KeyRule($key, $matches[1]);
        }
    }

    /**
     * 获取Member.
     *
     * @param string|object|null $object
     */
    public static function __getMemberRule($object = null): KeyRule
    {
        if (null === $object)
        {
            $class = static::__getRealClassName();
        }
        else
        {
            $class = BeanFactory::getObjectClass($object);
        }
        $staticMemberRules = &self::$memberRules;
        if (isset($staticMemberRules[$class]))
        {
            return $staticMemberRules[$class];
        }
        else
        {
            /** @var RedisEntity|null $redisEntity */
            $redisEntity = AnnotationManager::getClassAnnotations($class, RedisEntity::class, true, true);
            $key = $redisEntity ? $redisEntity->member : '';
            preg_match_all('/{([^}]+)}/', $key, $matches);

            return $staticMemberRules[$class] = new KeyRule($key, $matches[1]);
        }
    }

    public function __serialize(): array
    {
        $result = parent::__serialize();
        $result['key'] = $this->key;
        $result['member'] = $this->__member;
        $result['ttl'] = $this->__ttl;

        return $result;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        [
            'key'    => $this->key,
            'member' => $this->__member,
            'ttl'    => $this->__ttl,
        ] = $data;
    }
}
