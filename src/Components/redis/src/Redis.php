<?php

declare(strict_types=1);

namespace Imi\Redis;

use Imi\ConnectionCenter\Contract\IConnection;
use Imi\ConnectionCenter\Facade\ConnectionCenter;
use Imi\Pool\Interfaces\IPoolResource;
use Imi\Redis\Handler\IRedisHandler;

/**
 * Redis 快捷操作类.
 *
 * @method static mixed _prefix($key)
 * @method static mixed _serialize($value)
 * @method static mixed _unserialize($value)
 * @method static mixed append($key, $value)
 * @method static mixed auth($password)
 * @method static mixed bgSave()
 * @method static mixed bgrewriteaof()
 * @method static mixed bitcount($key)
 * @method static mixed bitop($operation, $ret_key, $key, ...$other_keys)
 * @method static mixed bitpos($key, $bit, $start = null, $end = null)
 * @method static mixed blPop($key, $timeout_or_key, ...$extra_args)
 * @method static mixed brPop($key, $timeout_or_key, ...$extra_args)
 * @method static mixed brpoplpush($src, $dst, $timeout)
 * @method static mixed bzPopMax($key, $timeout_or_key, ...$extra_args)
 * @method static mixed bzPopMin($key, $timeout_or_key, ...$extra_args)
 * @method static mixed clearLastError()
 * @method static mixed client($cmd, ...$args)
 * @method static mixed close()
 * @method static mixed command(...$args)
 * @method static mixed config($cmd, $key, $value = null)
 * @method static mixed connect($host, $port = null, $timeout = null, $retry_interval = null)
 * @method static mixed dbSize()
 * @method static mixed debug($key)
 * @method static mixed decr($key)
 * @method static mixed decrBy($key, $value)
 * @method static mixed del($key, ...$other_keys)
 * @method static mixed discard()
 * @method static mixed dump($key)
 * @method static mixed echo($msg)
 * @method static mixed eval($script, $args = null, $num_keys = null)
 * @method static mixed evalsha($script_sha, $args = null, $num_keys = null)
 * @method static mixed exec()
 * @method static mixed exists($key, ...$other_keys)
 * @method static mixed expire($key, $timeout)
 * @method static mixed expireAt($key, $timestamp)
 * @method static mixed flushAll($async = null)
 * @method static mixed flushDB($async = null)
 * @method static mixed geoadd($key, $lng, $lat, $member, ...$other_triples)
 * @method static mixed geodist($key, $src, $dst, $unit = null)
 * @method static mixed geohash($key, $member, ...$other_members)
 * @method static mixed geopos($key, $member, ...$other_members)
 * @method static mixed georadius($key, $lng, $lan, $radius, $unit, array $opts = null)
 * @method static mixed georadius_ro($key, $lng, $lan, $radius, $unit, array $opts = null)
 * @method static mixed georadiusbymember($key, $member, $radius, $unit, array $opts = null)
 * @method static mixed georadiusbymember_ro($key, $member, $radius, $unit, array $opts = null)
 * @method static mixed get($key)
 * @method static mixed getAuth()
 * @method static mixed getBit($key, $offset)
 * @method static mixed getDBNum()
 * @method static mixed getHost()
 * @method static mixed getLastError()
 * @method static mixed getMode()
 * @method static mixed getOption($option)
 * @method static mixed getPersistentId()
 * @method static mixed getPort()
 * @method static mixed getRange($key, $start, $end)
 * @method static mixed getReadTimeout()
 * @method static mixed getSet($key, $value)
 * @method static mixed getTimeout()
 * @method static mixed hDel($key, $member, ...$other_members)
 * @method static mixed hExists($key, $member)
 * @method static mixed hGet($key, $member)
 * @method static mixed hGetAll($key)
 * @method static mixed hIncrBy($key, $member, $value)
 * @method static mixed hIncrByFloat($key, $member, $value)
 * @method static mixed hKeys($key)
 * @method static mixed hLen($key)
 * @method static mixed hMget($key, array $keys)
 * @method static mixed hMset($key, array $pairs)
 * @method static mixed hSet($key, $member, $value)
 * @method static mixed hSetNx($key, $member, $value)
 * @method static mixed hStrLen($key, $member)
 * @method static mixed hVals($key)
 * @method static mixed incr($key)
 * @method static mixed incrBy($key, $value)
 * @method static mixed incrByFloat($key, $value)
 * @method static mixed info($option = null)
 * @method static mixed isConnected()
 * @method static mixed keys($pattern)
 * @method static mixed lInsert($key, $position, $pivot, $value)
 * @method static mixed lLen($key)
 * @method static mixed lPop($key)
 * @method static mixed lPush($key, $value)
 * @method static mixed lPushx($key, $value)
 * @method static mixed lSet($key, $index, $value)
 * @method static mixed lastSave()
 * @method static mixed lindex($key, $index)
 * @method static mixed lrange($key, $start, $end)
 * @method static mixed lrem($key, $value, $count)
 * @method static mixed ltrim($key, $start, $stop)
 * @method static mixed mget(array $keys)
 * @method static mixed migrate($host, $port, $key, $db, $timeout, $copy = null, $replace = null)
 * @method static mixed move($key, $dbindex)
 * @method static mixed mset(array $pairs)
 * @method static mixed msetnx(array $pairs)
 * @method static mixed multi($mode = null)
 * @method static mixed object($field, $key)
 * @method static mixed pconnect($host, $port = null, $timeout = null)
 * @method static mixed persist($key)
 * @method static mixed pexpire($key, $timestamp)
 * @method static mixed pexpireAt($key, $timestamp)
 * @method static mixed pfadd($key, array $elements)
 * @method static mixed pfcount($key)
 * @method static mixed pfmerge($dstkey, array $keys)
 * @method static mixed ping()
 * @method static mixed pipeline()
 * @method static mixed psetex($key, $expire, $value)
 * @method static mixed psubscribe(array $patterns, $callback)
 * @method static mixed pttl($key)
 * @method static mixed publish($channel, $message)
 * @method static mixed pubsub($cmd, ...$args)
 * @method static mixed punsubscribe($pattern, ...$other_patterns)
 * @method static mixed rPop($key)
 * @method static mixed rPush($key, $value)
 * @method static mixed rPushx($key, $value)
 * @method static mixed randomKey()
 * @method static mixed rawcommand($cmd, ...$args)
 * @method static mixed rename($key, $newkey)
 * @method static mixed renameNx($key, $newkey)
 * @method static mixed restore($ttl, $key, $value)
 * @method static mixed role()
 * @method static mixed rpoplpush($src, $dst)
 * @method static mixed sAdd($key, $value)
 * @method static mixed sAddArray($key, array $options)
 * @method static mixed sDiff($key, ...$other_keys)
 * @method static mixed sDiffStore($dst, $key, ...$other_keys)
 * @method static mixed sInter($key, ...$other_keys)
 * @method static mixed sInterStore($dst, $key, ...$other_keys)
 * @method static mixed sMembers($key)
 * @method static mixed sMove($src, $dst, $value)
 * @method static mixed sPop($key)
 * @method static mixed sRandMember($key, $count = null)
 * @method static mixed sUnion($key, ...$other_keys)
 * @method static mixed sUnionStore($dst, $key, ...$other_keys)
 * @method static mixed save()
 * @method static mixed scard($key)
 * @method static mixed script($cmd, ...$args)
 * @method static mixed select($dbindex)
 * @method static mixed set($key, $value, $opts = null)
 * @method static mixed setBit($key, $offset, $value)
 * @method static mixed setOption($option, $value)
 * @method static mixed setRange($key, $offset, $value)
 * @method static mixed setex($key, $expire, $value)
 * @method static mixed setnx($key, $value)
 * @method static mixed sismember($key, $value)
 * @method static mixed slaveof($host = null, $port = null)
 * @method static mixed slowlog($arg, $option = null)
 * @method static mixed sort($key, array $options = null)
 * @method static mixed sortAsc($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
 * @method static mixed sortAscAlpha($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
 * @method static mixed sortDesc($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
 * @method static mixed sortDescAlpha($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
 * @method static mixed srem($key, $member, ...$other_members)
 * @method static mixed strlen($key)
 * @method static mixed subscribe(array $channels, $callback)
 * @method static mixed swapdb($srcdb, $dstdb)
 * @method static mixed time()
 * @method static mixed ttl($key)
 * @method static mixed type($key)
 * @method static mixed unlink($key, ...$other_keys)
 * @method static mixed unsubscribe($channel, ...$other_channels)
 * @method static mixed unwatch()
 * @method static mixed wait($numslaves, $timeout)
 * @method static mixed watch($key, ...$other_keys)
 * @method static mixed xack($str_key, $str_group, array $arr_ids)
 * @method static mixed xadd($str_key, $str_id, array $arr_fields, $i_maxlen = null, $boo_approximate = null)
 * @method static mixed xclaim($str_key, $str_group, $str_consumer, $i_min_idle, array $arr_ids, array $arr_opts = null)
 * @method static mixed xdel($str_key, array $arr_ids)
 * @method static mixed xgroup($str_operation, $str_key = null, $str_arg1 = null, $str_arg2 = null, $str_arg3 = null)
 * @method static mixed xinfo($str_cmd, $str_key = null, $str_group = null)
 * @method static mixed xlen($key)
 * @method static mixed xpending($str_key, $str_group, $str_start = null, $str_end = null, $i_count = null, $str_consumer = null)
 * @method static mixed xrange($str_key, $str_start, $str_end, $i_count = null)
 * @method static mixed xread(array $arr_streams, $i_count = null, $i_block = null)
 * @method static mixed xreadgroup($str_group, $str_consumer, array $arr_streams, $i_count = null, $i_block = null)
 * @method static mixed xrevrange($str_key, $str_start, $str_end, $i_count = null)
 * @method static mixed xtrim($str_key, $i_maxlen, $boo_approximate = null)
 * @method static mixed zAdd($key, $score, $value)
 * @method static mixed zCard($key)
 * @method static mixed zCount($key, $min, $max)
 * @method static mixed zIncrBy($key, $value, $member)
 * @method static mixed zLexCount($key, $min, $max)
 * @method static mixed zPopMax($key)
 * @method static mixed zPopMin($key)
 * @method static mixed zRange($key, $start, $end, $scores = null)
 * @method static mixed zRangeByLex($key, $min, $max, $offset = null, $limit = null)
 * @method static mixed zRangeByScore($key, $start, $end, array $options = null)
 * @method static mixed zRank($key, $member)
 * @method static mixed zRem($key, $member, ...$other_members)
 * @method static mixed zRemRangeByLex($key, $min, $max)
 * @method static mixed zRemRangeByRank($key, $start, $end)
 * @method static mixed zRemRangeByScore($key, $min, $max)
 * @method static mixed zRevRange($key, $start, $end, $scores = null)
 * @method static mixed zRevRangeByLex($key, $min, $max, $offset = null, $limit = null)
 * @method static mixed zRevRangeByScore($key, $start, $end, array $options = null)
 * @method static mixed zRevRank($key, $member)
 * @method static mixed zScore($key, $member)
 * @method static mixed zinterstore($key, array $keys, ?array $weights = null, $aggregate = null)
 * @method static mixed zunionstore($key, array $keys, ?array $weights = null, $aggregate = null)
 * @method static mixed delete($key, ...$other_keys)
 * @method static mixed evaluate($script, $args = null, $num_keys = null)
 * @method static mixed evaluateSha($script_sha, $args = null, $num_keys = null)
 * @method static mixed getKeys($pattern)
 * @method static mixed getMultiple(array $keys)
 * @method static mixed lGet($key, $index)
 * @method static mixed lGetRange($key, $start, $end)
 * @method static mixed lRemove($key, $value, $count)
 * @method static mixed lSize($key)
 * @method static mixed listTrim($key, $start, $stop)
 * @method static mixed open($host, $port = null, $timeout = null, $retry_interval = null)
 * @method static mixed popen($host, $port = null, $timeout = null)
 * @method static mixed renameKey($key, $newkey)
 * @method static mixed sContains($key, $value)
 * @method static mixed sGetMembers($key)
 * @method static mixed sRemove($key, $member, ...$other_members)
 * @method static mixed sSize($key)
 * @method static mixed sendEcho($msg)
 * @method static mixed setTimeout($key, $timeout)
 * @method static mixed substr($key, $start, $end)
 * @method static mixed zDelete($key, $member, ...$other_members)
 * @method static mixed zDeleteRangeByRank($key, $min, $max)
 * @method static mixed zDeleteRangeByScore($key, $min, $max)
 * @method static mixed zInter($key, array $keys, ?array $weights = null, $aggregate = null)
 * @method static mixed zRemove($key, $member, ...$other_members)
 * @method static mixed zRemoveRangeByScore($key, $min, $max)
 * @method static mixed zReverseRange($key, $start, $end, $scores = null)
 * @method static mixed zSize($key)
 * @method static mixed zUnion($key, array $keys, ?array $weights = null, $aggregate = null)
 * @method static mixed evalEx(string $script, $args = null, $num_keys = null)
 * @method static array hMGetAll(array $keys)
 * @method static mixed scanEach(?string $pattern = null, int $count = 0)
 * @method static mixed hscanEach(string $key, ?string $pattern = null, int $count = 0)
 * @method static mixed sscanEach(string $key, ?string $pattern = null, int $count = 0)
 * @method static mixed zscanEach(string $key, ?string $pattern = null, int $count = 0)
 */
class Redis
{
    use \Imi\Util\Traits\TStaticClass;

    public static function __callStatic(string $name, array $arguments): mixed
    {
        return RedisManager::getInstance()->{$name}(...$arguments);
    }

    /**
     * 使用回调来使用池子中的资源，无需手动释放
     * 回调有 1 个参数：$instance(操作实例对象，Imi\Redis\Handler\IRedisHandler 类型)
     * 本方法返回值为回调的返回值
     */
    public static function use(callable $callable, ?string $poolName = null): mixed
    {
        return RedisManager::use($poolName, static fn (IConnection $resource, IRedisHandler $redis) => $callable($redis));
    }

    /**
     * scan.
     */
    public static function scan(?int &$iterator, ?string $pattern = null, int $count = 0): mixed
    {
        // todo 只适用于 phpredis
        return RedisManager::getInstance()->scan($iterator, $pattern, $count);
    }

    /**
     * hscan.
     */
    public static function hscan(string $key, ?int &$iterator, ?string $pattern = null, int $count = 0): mixed
    {
        // todo 只适用于 phpredis
        return RedisManager::getInstance()->hscan($key, $iterator, $pattern, $count);
    }

    /**
     * sscan.
     */
    public static function sscan(string $key, ?int &$iterator, ?string $pattern = null, int $count = 0): mixed
    {
        // todo 只适用于 phpredis
        return RedisManager::getInstance()->sscan($key, $iterator, $pattern, $count);
    }

    /**
     * zscan.
     */
    public static function zscan(string $key, ?int &$iterator, ?string $pattern = null, int $count = 0): mixed
    {
        // todo 只适用于 phpredis
        return RedisManager::getInstance()->zscan($key, $iterator, $pattern, $count);
    }
}
