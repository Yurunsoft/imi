<?php

declare(strict_types=1);

namespace Imi\Model;

use Imi\App;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\BeanFactory;
use Imi\Bean\ReflectionUtil;
use Imi\Model\Annotation\Column;
use Imi\Model\Annotation\RedisEntity;
use Imi\Model\Enum\RedisStorageMode;
use Imi\Model\Event\ModelEvents;
use Imi\Model\Event\Param\InitEventParam;
use Imi\Model\Key\KeyRule;
use Imi\Redis\Handler\IRedisHandler;
use Imi\Redis\Handler\PhpRedisHandler;
use Imi\Redis\Handler\PredisClusterHandler;
use Imi\Redis\Handler\PredisHandler;
use Imi\Redis\RedisManager;
use Imi\Util\Format\IFormat;
use Imi\Util\Imi;

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
     * 字段类型缓存.
     */
    protected static array $fieldTypeCache = [];

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
     * 动态模型集合.
     */
    protected static array $__forks = [];

    /**
     * @param class-string<RedisModel>|RedisModel|null $object
     */
    public static function __getRedisEntity(string|self|null $object = null): ?RedisEntity
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
        $meta = $this->__meta;
        $isBean = $meta->isBean();
        if ($isBean)
        {
            // 初始化前
            $this->dispatch(new InitEventParam(ModelEvents::BEFORE_INIT, $this, $data));
        }

        if ($data)
        {
            $this->__originData = $data;
            $fieldAnnotations = $meta->getFields();
            $class = $meta->getRealModelClass();
            if (!isset(self::$fieldTypeCache[$class]))
            {
                self::$fieldTypeCache[$class] = [];
            }
            foreach ($data as $k => $v)
            {
                if (isset($fieldAnnotations[$k]))
                {
                    $fieldAnnotation = $fieldAnnotations[$k];
                }
                else
                {
                    $fieldAnnotation = null;
                }
                if ($fieldAnnotation && \is_string($v))
                {
                    if (!isset(self::$fieldTypeCache[$class][$k]))
                    {
                        if ($type = (new \ReflectionProperty($class, $k))->getType())
                        {
                            foreach (['int', 'float', 'bool'] as $checkType)
                            {
                                if (ReflectionUtil::allowsType($type, $checkType))
                                {
                                    self::$fieldTypeCache[$class][$k] = $checkType;
                                    break;
                                }
                            }
                        }
                    }
                    /** @var \Imi\Model\Annotation\Column $fieldAnnotation */
                    switch ($fieldAnnotation->type ?? self::$fieldTypeCache[$class][$k] ?? null)
                    {
                        case 'json':
                            $fieldsJsonDecode ??= $meta->getFieldsJsonDecode();
                            if (isset($fieldsJsonDecode[$k][0]))
                            {
                                $realJsonDecode = $fieldsJsonDecode[$k][0];
                            }
                            else
                            {
                                $realJsonDecode = ($jsonDecode ??= ($meta->getJsonDecode() ?? false));
                            }
                            if ($realJsonDecode)
                            {
                                $value = json_decode($v, $realJsonDecode->associative, $realJsonDecode->depth, $realJsonDecode->flags);
                            }
                            else
                            {
                                $value = json_decode($v, true);
                            }
                            if (\JSON_ERROR_NONE === json_last_error())
                            {
                                if ($realJsonDecode)
                                {
                                    /** @var \Imi\Model\Annotation\JsonDecode $realJsonDecode */
                                    $wrap = $realJsonDecode->wrap;
                                    $classExists = class_exists($wrap);
                                    if ($realJsonDecode->arrayWrap)
                                    {
                                        $v = [];
                                        foreach ($value as $key => $_value)
                                        {
                                            if ($classExists)
                                            {
                                                $v[$key] = new $wrap($_value);
                                            }
                                            else
                                            {
                                                $v[$key] = $wrap($_value);
                                            }
                                        }
                                    }
                                    else
                                    {
                                        if ($classExists)
                                        {
                                            $v = new $wrap($value);
                                        }
                                        else
                                        {
                                            $v = $wrap($value);
                                        }
                                    }
                                }
                                else
                                {
                                    $v = $value;
                                }
                            }
                            break;
                        case 'list':
                            if ('' === $v)
                            {
                                $v = [];
                            }
                            elseif (null !== $fieldAnnotation->listSeparator)
                            {
                                $v = '' === $fieldAnnotation->listSeparator ? [] : explode($fieldAnnotation->listSeparator, $v);
                            }
                            break;
                        case 'set':
                            if ('' === $v)
                            {
                                $v = [];
                            }
                            else
                            {
                                $v = explode(',', $v);
                            }
                            break;
                        case 'int':
                            $v = (int) $v;
                            break;
                        case 'float':
                            $v = (float) $v;
                            break;
                        case 'bool':
                            $v = (bool) $v;
                            break;
                    }
                }
                $this[$k] = $v;
            }
        }

        if ($isBean)
        {
            // 初始化后
            $this->dispatch(new InitEventParam(ModelEvents::AFTER_INIT, $this, $data));
        }
        $this->__ttl = static::__getRedisEntity($this)->ttl;
    }

    /**
     * 查找一条记录.
     *
     * @return static|null
     */
    public static function find(string|array $condition = []): ?self
    {
        /** @var \Imi\Model\Annotation\RedisEntity $redisEntity */
        $redisEntity = static::__getRedisEntity(static::__getRealClassName());
        $key = static::generateKey($condition);

        /** @var IFormat|null $formatter */
        $formatter = null !== $redisEntity->formatter ? App::getBean($redisEntity->formatter) : null;

        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                $data = static::__getRedis()->get($key);
                if ($data && null !== $formatter)
                {
                    $data = $formatter->decode($data);
                }
                break;
            case RedisStorageMode::HASH:
                $member = static::generateMember($condition);
                $data = static::__getRedis()->hGet($key, $member);
                if ($data && null !== $formatter)
                {
                    $data = $formatter->decode($data);
                }
                break;
            case RedisStorageMode::HASH_OBJECT:
                $data = static::__getRedis()->hGetAll($key);
                if ($data && null !== $formatter)
                {
                    foreach ($data as &$val)
                    {
                        $val = $formatter->decode($val);
                    }
                    unset($val);
                }
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
     * @return static[]
     */
    public static function select(mixed ...$conditions): array
    {
        /** @var \Imi\Model\Annotation\RedisEntity $redisEntity */
        $redisEntity = static::__getRedisEntity(static::__getRealClassName());
        /** @var IFormat|null $formatter */
        $formatter = null !== $redisEntity->formatter ? App::getBean($redisEntity->formatter) : null;
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
                $redis = static::__getRedis();
                if ($redis instanceof PredisClusterHandler)
                {
                    $datas = [];
                    foreach ($redis->getSlotGroupByKeys($keys) as $slotGroupByKey)
                    {
                        array_push($datas, ...$redis->mget($slotGroupByKey));
                    }
                }
                else
                {
                    $datas = $redis->mget($keys);
                }
                if ($datas)
                {
                    $list = [];
                    foreach ($datas as $i => $data)
                    {
                        if (null !== $data && false !== $data)
                        {
                            if (null !== $formatter)
                            {
                                $data = $formatter->decode($data);
                            }
                            $record = static::createFromRecord($data);
                            $record->key = $keys[$i];
                            $list[] = $record;
                        }
                    }

                    return $list;
                }

                return [];
            case RedisStorageMode::HASH:
                $members = [];
                if ($conditions)
                {
                    foreach ($conditions as $condition)
                    {
                        $members[] = static::generateMember($condition);
                    }
                }
                $redis = static::__getRedis();
                if ($keys)
                {
                    $list = [];
                    foreach (array_unique($keys) as $key)
                    {
                        $datas = $redis->hMget($key, $members);
                        if ($datas)
                        {
                            foreach ($datas as $i => $data)
                            {
                                if (null !== $data)
                                {
                                    if (null !== $formatter)
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

                    return $list;
                }

                return [];
            case RedisStorageMode::HASH_OBJECT:
                $redis = static::__getRedis();
                if ($keys)
                {
                    $list = [];
                    foreach ($keys as $key)
                    {
                        $data = $redis->hGetAll($key);
                        if (null !== $formatter)
                        {
                            foreach ($data as &$val)
                            {
                                $val = $formatter->decode($val);
                            }
                            unset($val);
                        }
                        $record = static::createFromRecord($data);
                        $record->key = $key;
                        $list[] = $record;
                    }

                    return $list;
                }

                return [];
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
        $data = iterator_to_array($this);
        $this->parseSaveData($data);

        /** @var IFormat|null $formatter */
        $formatter = null !== $redisEntity->formatter ? App::getBean($redisEntity->formatter) : null;

        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                if (null !== $formatter)
                {
                    $data = $formatter->encode($data);
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
                if (null !== $formatter)
                {
                    $data = $formatter->encode($data);
                }

                return false !== $redis->hSet($this->__getKey(), $this->__getMember(), $data);
            case RedisStorageMode::HASH_OBJECT:
                $key = $this->__getKey();
                if (null !== $formatter)
                {
                    foreach ($data as &$val)
                    {
                        $val = $formatter->encode($val);
                    }
                    unset($val);
                }
                $result = $redis->hMset($key, $data);
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

        return match ($redisEntity->storage)
        {
            RedisStorageMode::STRING      => (int) static::__getRedis($this)->del($this->__getKey()) > 0,
            RedisStorageMode::HASH        => (int) static::__getRedis($this)->hDel($this->__getKey(), $this->__getMember()) > 0,
            RedisStorageMode::HASH_OBJECT => (int) static::__getRedis($this)->del($this->__getKey()) > 0,
            default                       => throw new \InvalidArgumentException(sprintf('Invalid RedisEntity->storage %s', $redisEntity->storage)),
        };
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
        $data = iterator_to_array($this);
        $this->parseSaveData($data);

        /** @var IFormat|null $formatter */
        $formatter = null !== $redisEntity->formatter ? App::getBean($redisEntity->formatter) : null;

        switch ($redisEntity->storage)
        {
            case RedisStorageMode::STRING:
                if (null !== $formatter)
                {
                    $data = $formatter->encode($data);
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
                if (null !== $formatter)
                {
                    $data = $formatter->encode($data);
                }
                $redis = static::__getRedis($this);
                $data = $redis->_serialize($data);

                return (bool) $redis->evalEx(<<<'LUA'
                if (ARGV[2] == redis.call('hget', KEYS[1], ARGV[1])) then
                    return redis.call('hdel', KEYS[1], ARGV[1])
                else
                    return 0
                end
                LUA, [$this->__getKey(), $this->__getMember(), $data], 1);
            case RedisStorageMode::HASH_OBJECT:
                $argv = [];
                $redis = static::__getRedis($this);
                foreach ($data as $key => $value)
                {
                    $argv[] = $key;

                    $value = null === $formatter ? $value : $formatter->encode($value);
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
     */
    public static function deleteBatch(mixed ...$conditions): int
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
                $redis = static::__getRedis();
                if ($redis instanceof PredisClusterHandler)
                {
                    $result = 0;
                    foreach ($redis->getSlotGroupByKeys($keys) as $slotGroupByKey)
                    {
                        $result += $redis->del(...$slotGroupByKey) ?: 0;
                    }

                    return $result;
                }
                else
                {
                    return $redis->del(...$keys) ?: 0;
                }
                // no break
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

                $redis = static::__getRedis();
                if ($redis instanceof PredisClusterHandler)
                {
                    $result = 0;
                    foreach ($redis->getSlotGroupByKeys($keys) as $slotGroupByKey)
                    {
                        $result += $redis->del(...$slotGroupByKey) ?: 0;
                    }

                    return $result;
                }
                else
                {
                    return $redis->del(...$keys) ?: 0;
                }
                // no break
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
     */
    public static function generateKey(string|array $condition = []): string
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
     */
    public static function generateMember(string|array $condition = []): string
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
     * @return PhpRedisHandler|PredisHandler
     */
    public static function __getRedis(?self $redisModel = null): IRedisHandler
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
     */
    public static function __getKeyRule(string|self|null $object = null): KeyRule
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
            $redisEntity = static::__getRedisEntity($class);
            $key = $redisEntity ? $redisEntity->key : '';
            preg_match_all('/{([^}]+)}/', $key, $matches);

            return $staticKeyRules[$class] = new KeyRule($key, $matches[1]);
        }
    }

    /**
     * 获取Member.
     */
    public static function __getMemberRule(string|self|null $object = null): KeyRule
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
            $redisEntity = static::__getRedisEntity($object);
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

    protected function parseSaveData(array &$data): void
    {
        $meta = $this->__meta;
        $fieldAnnotations = $meta->getFields();
        foreach ($data as $name => &$value)
        {
            /** @var Column|null $columnAnnotation */
            $columnAnnotation = $fieldAnnotations[$name] ?? null;
            if (!$columnAnnotation || $columnAnnotation->virtual)
            {
                unset($data[$name]);
                continue;
            }
            switch ($columnAnnotation->type)
            {
                case 'json':
                    $fieldsJsonEncode ??= $meta->getFieldsJsonEncode();
                    if (isset($fieldsJsonEncode[$name][0]))
                    {
                        $realJsonEncode = $fieldsJsonEncode[$name][0];
                    }
                    else
                    {
                        $realJsonEncode = ($jsonEncode ??= ($meta->getJsonEncode() ?? false));
                    }
                    if (null === $value && $columnAnnotation->nullable)
                    {
                        // 当字段允许`null`时，使用原生`null`存储
                        $value = null;
                    }
                    elseif ($realJsonEncode)
                    {
                        $value = json_encode($value, $realJsonEncode->flags, $realJsonEncode->depth);
                    }
                    else
                    {
                        $value = json_encode($value, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
                    }
                    break;
                case 'list':
                    if (null !== $value && null !== $columnAnnotation->listSeparator)
                    {
                        $value = implode($columnAnnotation->listSeparator, $value);
                    }
                    break;
                case 'set':
                    $value = implode(',', $value);
                    break;
            }
        }
    }

    /**
     * Fork 模型.
     *
     * @param class-string<IFormat>|null $formatter
     *
     * @return class-string<static>
     */
    public static function fork(?string $poolName = null, ?string $formatter = null, ?int $db = null): string
    {
        // todo 觉得 model 的 meta 设置不统一， 建议 BaseModel 能提供更多底层支持扩展支持
        // 建议能传入 RedisEntity 对象的全部参数，能适用于更多场景
        $forks = &self::$__forks;
        if (isset($forks[static::class][$db][$poolName]))
        {
            return $forks[static::class][$db][$poolName];
        }
        $namespace = Imi::getClassNamespace(static::class);
        $entity = static::__getRedisEntity();
        if (null !== $db)
        {
            $entity->db = $db;
        }
        if (null !== $poolName)
        {
            $entity->poolName = $poolName;
        }
        if (null !== $formatter)
        {
            $entity->formatter = $formatter;
        }
        $entityData = var_export(serialize($entity), true);
        $class = str_replace('\\', '__', static::class . '\\' . md5($db . '\\' . $poolName . '\\' . $formatter));
        $extendsClass = static::class;

        Imi::eval(<<<PHP
        namespace {$namespace} {
            use Imi\Bean\Annotation\AnnotationManager;
            use Imi\Bean\BeanFactory;
            use Imi\Model\Annotation\RedisEntity;
            use Imi\Model\RedisModel;

            class {$class} extends \\{$extendsClass}
            {
                private static ?RedisEntity \$forkEntity = null;

                public static function __getRedisEntity(string|RedisModel|null \$object = null): ?RedisEntity
                {
                    if (null === self::\$forkEntity) {
                        self::\$forkEntity = unserialize({$entityData});
                    }

                    return self::\$forkEntity;
                }
            }
        }
        PHP);

        return $forks[static::class][$db][$poolName] = $namespace . '\\' . $class;
    }
}
