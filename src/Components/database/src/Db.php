<?php

declare(strict_types=1);

namespace Imi\Db;

use Imi\Config;
use Imi\ConnectionCenter\Contract\IConnection;
use Imi\ConnectionCenter\Facade\ConnectionCenter;
use Imi\Db\ConnectionCenter\DatabaseDriverConfig;
use Imi\Db\Interfaces\IDb;
use Imi\Db\Interfaces\IStatement;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Db\Query\Interfaces\IResult;
use Imi\Db\Query\QueryType;
use Imi\Db\Query\Result;

class Db
{
    use \Imi\Util\Traits\TStaticClass;

    /**
     * 获取数据库连接实例，每个上下文中共用一个.
     *
     * @param string|null $poolName  连接池名称
     * @param int         $queryType 查询类型
     */
    public static function getInstance(?string $poolName = null, int $queryType = QueryType::WRITE): IDb
    {
        return ConnectionCenter::getRequestContextConnection(self::parsePoolName($poolName, $queryType))->getInstance();
    }

    /**
     * 创建新的数据库连接实例.
     *
     * @param string|null $poolName  连接池名称
     * @param int         $queryType 查询类型
     */
    public static function createInstance(?string $poolName = null, int $queryType = QueryType::WRITE): IDb
    {
        return ConnectionCenter::createConnection(self::parsePoolName($poolName, $queryType));
    }

    /**
     * 获取新的数据库连接实例.
     *
     * 返回的是连接池连接对象，需调用 `getInstance()` 获取实际的对象进行操作.
     *
     * 连接对象释放即断开连接，操作期间需要保证连接对象不被释放.
     *
     * 如果不是特别必要，不推荐使用此方法！！！
     *
     * @param string|null $poolName  连接池名称
     * @param int         $queryType 查询类型
     */
    public static function getNewInstance(?string $poolName = null, int $queryType = QueryType::WRITE): IConnection
    {
        return ConnectionCenter::getConnection(self::parsePoolName($poolName, $queryType));
    }

    /**
     * 获取数据库连接实例配置.
     */
    public static function getInstanceConfig(?string $poolName = null, int $queryType = QueryType::WRITE): ?DatabaseDriverConfig
    {
        // @phpstan-ignore-next-line
        return ConnectionCenter::getConnectionManager(self::parsePoolName($poolName, $queryType))->getDriver()->getConnectionLoadBalancer()->choose();
    }

    /**
     * 返回一个查询器.
     */
    public static function query(?string $poolName = null, ?string $modelClass = null, ?int $queryType = null): IQuery
    {
        return self::getInstance($poolName, $queryType ?? QueryType::WRITE)->createQuery($modelClass);
    }

    /**
     * 处理连接池名称.
     */
    private static function parsePoolName(?string $poolName = null, int $queryType = QueryType::WRITE): string
    {
        if (null === $poolName || '' === $poolName)
        {
            $poolName = static::getDefaultPoolName($queryType);
        }
        else
        {
            switch ($queryType)
            {
                case QueryType::READ:
                    $newPoolName = $poolName . '.slave';
                    if (ConnectionCenter::hasConnectionManager($newPoolName))
                    {
                        $poolName = $newPoolName;
                    }
                    break;
                case QueryType::WRITE:
                default:
                    // 保持原样不做任何处理
                    break;
            }
        }

        return $poolName;
    }

    /**
     * 获取默认池子名称.
     *
     * @param int $queryType 查询类型
     */
    public static function getDefaultPoolName(int $queryType = QueryType::WRITE): string
    {
        $poolName = Config::get('@currentServer.db.defaultPool');
        if (null !== $poolName)
        {
            $poolName = self::parsePoolName($poolName, $queryType);
        }

        return $poolName;
    }

    /**
     * 使用回调来使用池子中的资源，无需手动释放
     * 回调有 1 个参数：$instance(操作实例对象)
     * 本方法返回值为回调的返回值
     */
    public static function use(callable $callable, ?string $poolName = null, int $queryType = QueryType::WRITE): mixed
    {
        $poolName = self::parsePoolName($poolName, $queryType);

        if (ConnectionCenter::hasConnectionManager($poolName))
        {
            $connection = ConnectionCenter::getConnection($poolName);

            return $callable($connection->getInstance());
        }
        else
        {
            return $callable(static::getInstance($poolName));
        }
    }

    /**
     * 使用回调来使用池子中的资源，无需手动释放，自动开启/提交/回滚事务
     * 回调有 1 个参数：$instance(操作实例对象)
     * 本方法返回值为回调的返回值
     */
    public static function transUse(callable $callable, ?string $poolName = null, int $queryType = QueryType::WRITE): mixed
    {
        $connection = ConnectionCenter::getConnection($poolName);

        return self::use(static fn () => static::trans($connection->getInstance(), $callable), $poolName, $queryType);
    }

    /**
     * 使用回调来使用当前上下文中的资源，无需手动释放，自动开启/提交/回滚事务
     * 回调有 1 个参数：$instance(操作实例对象)
     * 本方法返回值为回调的返回值
     */
    public static function transContext(callable $callable, ?string $poolName = null, int $queryType = QueryType::WRITE): mixed
    {
        return static::trans(static::getInstance($poolName, $queryType), $callable);
    }

    /**
     * 事务处理，自动开启/提交/回滚事务
     */
    public static function trans(IDb $db, callable $callable): mixed
    {
        try
        {
            $db->beginTransaction();
            $result = $callable($db); // 调用回调
            $db->commit();

            return $result;
        }
        catch (\Throwable $th)
        {
            // 回滚事务
            if ($db->inTransaction() && $db->isConnected())
            {
                $db->rollBack();
            }
            throw $th;
        }
    }

    /**
     * 执行 SQL 并返回受影响的行数.
     */
    public static function exec(string $sql, array $bindValues = [], ?string $poolName = null, int $queryType = QueryType::WRITE): int
    {
        if ($bindValues)
        {
            $stmt = self::getInstance($poolName, $queryType)->prepare($sql);
            if ($stmt->execute($bindValues))
            {
                return $stmt->rowCount();
            }
            else
            {
                return 0;
            }
        }
        else
        {
            return self::getInstance($poolName, $queryType)->exec($sql);
        }
    }

    /**
     * 执行 SQL 返回结果.
     */
    public static function select(string $sql, array $bindValues = [], ?string $poolName = null, int $queryType = QueryType::WRITE): ?IResult
    {
        $db = self::getInstance($poolName, $queryType);
        if ($bindValues)
        {
            $stmt = $db->prepare($sql);
            if (!$stmt->execute($bindValues))
            {
                return new Result(false);
            }
        }
        else
        {
            $stmt = $db->query($sql);
        }

        return new Result($stmt, null, true);
    }

    /**
     * 准备执行语句并返回一个语句对象
     */
    public static function prepare(string $sql, ?string $poolName = null, int $queryType = QueryType::WRITE): IStatement
    {
        return self::getInstance($poolName, $queryType)->prepare($sql);
    }

    /**
     * 尝试把绑定值渲染到的 sql 以获取实际可执行语句.
     */
    public static function debugSql(string $sql, array $bindValues): string
    {
        if (empty($bindValues))
        {
            return $sql;
        }
        if (array_is_list($bindValues))
        {
            $sql = str_replace('??', '__mask__', $sql);

            foreach ($bindValues as $value)
            {
                $sql = preg_replace('/\?/', var_export($value, true), (string) $sql, 1);
            }

            return str_replace('__mask__', '??', (string) $sql);
        }
        else
        {
            $bindValues = array_reverse($bindValues);
            $values = array_map(static fn ($val) => var_export($val, true), array_values($bindValues));

            return str_replace(array_keys($bindValues), $values, $sql);
        }
    }
}
