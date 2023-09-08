<?php

declare(strict_types=1);

namespace Imi\Model\Cli\Model;

use Imi\Cli\Annotation\Argument;
use Imi\Cli\Annotation\Command;
use Imi\Cli\Annotation\CommandAction;
use Imi\Cli\Annotation\Option;
use Imi\Cli\ArgType;
use Imi\Cli\Contract\BaseCommand;
use Imi\Config;
use Imi\Db\Db;
use Imi\Db\Mysql\Util\SqlUtil;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Event\Event;
use Imi\Model\Annotation\DDL;
use Imi\Model\Cli\Model\Event\Param\AfterGenerateModel;
use Imi\Model\Cli\Model\Event\Param\AfterGenerateModels;
use Imi\Model\Cli\Model\Event\Param\BeforeGenerateModel;
use Imi\Model\Cli\Model\Event\Param\BeforeGenerateModels;
use Imi\Model\Model;
use Imi\Util\ArrayUtil;
use Imi\Util\File;
use Imi\Util\Imi;
use Imi\Util\Text;

/**
 * @Command("generate")
 */
class ModelGenerate extends BaseCommand
{
    /**
     * 生成数据库中所有表的模型文件，如果设置了`include`或`exclude`，则按照相应规则过滤表。
     *
     * @CommandAction(name="model", description="模型生成", dynamicOptions=true)
     *
     * @Argument(name="namespace", type=ArgType::STRING, required=true, comments="生成的Model所在命名空间")
     * @Argument(name="baseClass", type=ArgType::STRING, default="Imi\Model\Model", comments="生成的Model所继承的基类,默认\Imi\Model\Model,可选")
     *
     * @Option(name="database", type=ArgType::STRING, comments="数据库名，不传则取连接池默认配置的库名")
     * @Option(name="poolName", type=ArgType::STRING, comments="连接池名称，不传则取默认连接池")
     * @Option(name="prefix", type=ArgType::ARRAY_EX, default={}, comments="传值则去除该表前缀，以半角逗号分隔多个前缀")
     * @Option(name="include", type=ArgType::ARRAY_EX, default={}, comments="要包含的表名，以半角逗号分隔")
     * @Option(name="exclude", type=ArgType::ARRAY_EX, default={}, comments="要排除的表名，以半角逗号分隔")
     * @Option(name="override", type=ArgType::STRING, default=false, comments="是否覆盖已存在的文件，请慎重！true-全覆盖;false-不覆盖;base-覆盖基类;model-覆盖模型类;默认缺省状态为false")
     * @Option(name="config", type=ArgType::STRING, default=true, comments="配置文件。true-项目配置；false-忽略配置；php配置文件名-使用该配置文件。默认为true")
     * @Option(name="basePath", type=ArgType::STRING, default=null, comments="指定命名空间对应的基准路径，可选")
     * @Option(name="entity", type=ArgType::BOOLEAN, default=true, comments="序列化时是否使用驼峰命名(true or false),默认true,可选")
     * @Option(name="sqlSingleLine", type=ArgType::BOOLEAN, default=false, comments="生成的SQL为单行,默认false,可选")
     * @Option(name="lengthCheck", type=ArgType::BOOLEAN, default=false, comments="是否检查字符串字段长度,可选")
     * @Option(name="ddlEncode", type=ArgType::STRING, comments="DDL 编码函数", default="")
     * @Option(name="ddlDecode", type=ArgType::STRING, comments="DDL 解码函数", default="")
     * @Option(name="bean", type=ArgType::BOOL, comments="模型对象是否作为 bean 类使用", default=true)
     * @Option(name="incrUpdate", type=ArgType::BOOL, comments="模型是否启用增量更新", default=false)
     *
     * @param string|bool $override
     * @param string|bool $config
     */
    public function generate(string $namespace, string $baseClass, ?string $database, ?string $poolName, array $prefix, array $include, array $exclude, $override, $config, ?string $basePath, bool $entity, bool $sqlSingleLine, bool $lengthCheck, string $ddlEncode, string $ddlDecode, bool $bean, bool $incrUpdate): void
    {
        Event::trigger(BeforeGenerateModels::class, [], $this, BeforeGenerateModels::class);
        $db = Db::getInstance($poolName);
        $tablePrefix = $db->getOption()['prefix'] ?? '';
        if ('' !== $tablePrefix && !\in_array($tablePrefix, $prefix))
        {
            $prefix[] = $tablePrefix;
        }
        $override = (string) $override;
        switch ($override)
        {
            case 'base':
                break;
            case 'model':
                break;
            default:
                $override = (bool) json_decode($override, false);
        }
        if (\in_array($config, ['true', 'false'], true))
        {
            $config = (bool) json_decode($config, false);
        }
        if (true === $config)
        {
            $configData = Config::get('@app.tools.generate/model');
        }
        elseif (\is_string($config))
        {
            $configData = include $config;
            $configData = $configData['tools']['generate/model'];
        }
        else
        {
            $configData = null;
        }
        $query = Db::query($poolName);
        // 数据库
        if (null === $database)
        {
            $database = $query->execute('select database()')->getScalar();
        }
        // 表
        $list = $query->tableRaw('information_schema.TABLES')
                      ->where('TABLE_SCHEMA', '=', $database)
                      ->whereIn('TABLE_TYPE', [
                          'BASE TABLE',
                          'VIEW',
                      ])
                      ->field('TABLE_NAME', 'TABLE_TYPE', 'TABLE_COMMENT')
                      ->select()
                      ->getArray();
        // model保存路径
        if (null === $basePath)
        {
            $modelPath = Imi::getNamespacePath($namespace, true);
        }
        else
        {
            $modelPath = $basePath;
        }
        if (null === $modelPath)
        {
            $this->output->writeln('<error>Namespace</error> <comment>' . $namespace . '</comment> <error>cannot found</error>');
            exit(255);
        }
        $this->output->writeln('<info>modelPath:</info> <comment>' . $modelPath . '</comment>');
        if (empty($baseClass) || !class_exists($baseClass))
        {
            echo 'BaseClass ', $baseClass, ' cannot found', \PHP_EOL;

            return;
        }
        if (Model::class !== $baseClass && !is_subclass_of($baseClass, Model::class))
        {
            echo 'BaseClass ', $baseClass, ' not extends ', Model::class, \PHP_EOL;

            return;
        }
        $this->output->writeln('<info>baseClass:</info> <comment>' . $baseClass . '</comment>');
        File::createDir($modelPath);
        $baseModelPath = $modelPath . '/Base';
        File::createDir($baseModelPath);
        foreach ($list as $item)
        {
            $table = $item['TABLE_NAME'];
            if (!$this->checkTable($table, $include, $exclude))
            {
                // 不符合$include和$exclude
                continue;
            }
            $className = $this->getClassName($table, $prefix);
            if (isset($configData['relation'][$table]))
            {
                // 按表指定，下个大版本即将废弃 @deprecated 3.0
                $configItem = $configData['relation'][$table];
                $modelNamespace = $configItem['namespace'] ?? $namespace;
                $path = Imi::getNamespacePath($modelNamespace, true);
                if (null === $path)
                {
                    $this->output->writeln('<error>Namespace</error> <comment>' . $modelNamespace . '</comment> <error>cannot found</error>');
                    exit(255);
                }
                File::createDir($path);
                $basePath = $path . '/Base';
                File::createDir($basePath);
                $fileName = File::path($path, $className . '.php');
                $withRecords = $configItem['withRecords'] ?? false;
            }
            else
            {
                $hasResult = false;
                $withRecords = false;
                $fileName = '';
                $modelNamespace = '';
                $tableConfig = null;
                // 按命名空间指定
                foreach ($configData['namespace'] ?? [] as $namespaceName => $namespaceItem)
                {
                    if (($tableConfig = ($namespaceItem['tables'][$table] ?? null)) || \in_array($table, $namespaceItem['tables'] ?? []))
                    {
                        $modelNamespace = $namespaceName;
                        $path = Imi::getNamespacePath($modelNamespace, true);
                        if (null === $path)
                        {
                            $this->output->writeln('<error>Namespace</error> <comment>' . $modelNamespace . '</comment> <error>cannot found</error>');
                            exit(255);
                        }
                        File::createDir($path);
                        $basePath = $path . '/Base';
                        File::createDir($basePath);
                        $fileName = File::path($path, $className . '.php');
                        $hasResult = true;
                        $withRecords = ($tableConfig['withRecords'] ?? null) ?? \in_array($table, $namespaceItem['withRecords'] ?? []);
                        break;
                    }
                }
                if (!$hasResult)
                {
                    $modelNamespace = $namespace;
                    $fileName = File::path($modelPath, $className . '.php');
                    $basePath = $baseModelPath;
                }
            }
            if (false === $override && is_file($fileName))
            {
                // 不覆盖
                $this->output->writeln('Skip <info>' . $table . '</info>');
                continue;
            }
            $rawDDL = $this->getDDL($query, $table, $database);
            if ($withRecords)
            {
                $dataList = $query->tablePrefix('')->table($table, null, $database)->select()->getArray();
                $rawDDL .= ';' . \PHP_EOL . SqlUtil::buildInsertSql($query, $table, $dataList);
            }
            $fullClassName = $modelNamespace . '\\' . $className;
            if ($sqlSingleLine)
            {
                $rawDDL = str_replace(\PHP_EOL, ' ', $rawDDL);
            }
            $ddlDecodeTmp = null;
            if ('' === $ddlEncode)
            {
                // 未指定编码方式，判断存在注释时，base64 编码
                if (str_contains($rawDDL, '/*'))
                {
                    $ddl = base64_encode($rawDDL);
                    $ddlDecodeTmp = 'base64_decode';
                }
                else
                {
                    $ddl = $rawDDL;
                }
            }
            else
            {
                $ddl = $ddlEncode($rawDDL);
            }
            if ($usePrefix = ('' !== $tablePrefix && str_starts_with($table, $tablePrefix)))
            {
                $tableName = Text::ltrimText($table, $tablePrefix);
            }
            else
            {
                $tableName = $table;
            }
            $tableComment = '' === $item['TABLE_COMMENT'] ? $tableName : $item['TABLE_COMMENT'];
            if ('@' === ($tableComment[0] ?? ''))
            {
                $tableComment = '@' . $tableComment;
            }
            $data = [
                'namespace'     => $modelNamespace,
                'baseClassName' => $baseClass,
                'className'     => $className,
                'fullClassName' => $fullClassName,
                'tableName'     => $tableName,
                'table'         => [
                    'name'      => $tableName,
                    'id'        => [],
                    'usePrefix' => $usePrefix,
                ],
                'fields'        => [],
                'camel'         => $entity,
                'bean'          => $tableConfig['bean'] ?? $bean,
                'incrUpdate'    => $tableConfig['incrUpdate'] ?? $incrUpdate,
                'poolName'      => $poolName,
                'ddl'           => $ddl,
                'rawDDL'        => $rawDDL,
                'ddlDecode'     => $ddlDecodeTmp ?? ('' === $ddlDecode ? null : $ddlDecode),
                'tableComment'  => $tableComment,
                'lengthCheck'   => $lengthCheck,
            ];
            $fields = $query->execute(sprintf('show full columns from `%s`.`%s`', $database, $table))->getArray();
            $typeDefinitions = [];
            foreach ($fields as $field)
            {
                $typeDefinitions[$field['Field']] = ($tableConfig['fields'][$field['Field']]['typeDefinition'] ?? null) ?? ($configData['relation'][$table]['fields'][$field['Field']]['typeDefinition'] ?? true);
            }
            $pks = $query->execute(sprintf('SHOW KEYS FROM `%s`.`%s` where Key_name = \'PRIMARY\'', $database, $table))->getArray();
            $pks = ArrayUtil::columnToKey($pks, 'Column_name');

            $this->parseFields($fields, $pks, $data, $typeDefinitions);

            $baseFileName = File::path($basePath, $className . 'Base.php');

            Event::trigger(BeforeGenerateModel::class, $data, $this, BeforeGenerateModel::class);
            if (!is_file($baseFileName) || true === $override || 'base' === $override)
            {
                $this->output->writeln('Generating <info>' . $table . '</info> BaseClass...');
                $baseContent = $this->renderTemplate('base-template', $data);
                File::putContents($baseFileName, $baseContent);
            }

            if (!is_file($fileName) || true === $override || 'model' === $override)
            {
                $this->output->writeln('Generating <info>' . $table . '</info> Class...');
                $content = $this->renderTemplate('template', $data);
                File::putContents($fileName, $content);
            }
            Event::trigger(AfterGenerateModel::class, $data, $this, AfterGenerateModel::class);
        }
        $this->output->writeln('<info>Complete</info>');

        Event::trigger(AfterGenerateModels::class, [], $this, AfterGenerateModels::class);
    }

    /**
     * 检查表是否生成.
     */
    private function checkTable(string $table, array $include, array $exclude): bool
    {
        if (\in_array($table, $exclude))
        {
            return false;
        }

        return !isset($include[0]) || \in_array($table, $include);
    }

    /**
     * 表名转短类名.
     */
    private function getClassName(string $table, array $prefixs): string
    {
        foreach ($prefixs as $prefix)
        {
            $prefixLen = \strlen($prefix);
            if (substr($table, 0, $prefixLen) === $prefix)
            {
                $table = substr($table, $prefixLen);
                break;
            }
        }

        return Text::toPascalName($table);
    }

    /**
     * 处理字段信息.
     */
    private function parseFields(array $fields, array $pks, ?array &$data, array $typeDefinitions): void
    {
        foreach ($fields as $field)
        {
            $this->parseFieldType($field['Type'], $typeName, $length, $accuracy, $unsigned);
            $isPk = isset($pks[$field['Field']]);
            [$phpType, $phpDefinitionType, $typeConvert] = $this->dbFieldTypeToPhp($typeName);
            $data['fields'][] = [
                'name'              => $field['Field'],
                'varName'           => Text::toCamelName($field['Field']),
                'type'              => $typeName,
                'phpType'           => $phpType,
                'phpDefinitionType' => $phpDefinitionType,
                'typeConvert'       => $typeConvert,
                'length'            => $length,
                'accuracy'          => $accuracy,
                'nullable'          => 'YES' === $field['Null'],
                'default'           => $field['Default'],
                'defaultValue'      => $this->parseFieldDefaultValue($typeName, $field['Default']),
                'isPrimaryKey'      => $isPk,
                'primaryKeyIndex'   => $primaryKeyIndex = ($pks[$field['Field']]['Seq_in_index'] ?? 0) - 1,
                'isAutoIncrement'   => str_contains($field['Extra'], 'auto_increment'),
                'comment'           => $field['Comment'],
                'typeDefinition'    => $typeDefinitions[$field['Field']],
                'ref'               => 'json' === $typeName,
                'unsigned'          => $unsigned,
                'virtual'           => str_contains($field['Extra'], 'VIRTUAL GENERATED'),
            ];
            if ($isPk)
            {
                $data['table']['id'][$primaryKeyIndex] = $field['Field'];
            }
        }
        ksort($data['table']['id']);
        $data['table']['id'] = array_values($data['table']['id']);
    }

    /**
     * 处理类似varchar(32)和decimal(10,2)格式的字段类型.
     *
     * @param string $typeName
     * @param int    $length
     * @param int    $accuracy
     */
    public function parseFieldType(string $text, ?string &$typeName, ?int &$length, ?int &$accuracy, ?bool &$unsigned): bool
    {
        if (preg_match('/([^(\s]+)(\((\d+)(,(\d+))?\))?(?<unsigned> unsigned)?/', $text, $match))
        {
            $typeName = $match[1];
            $length = (int) ($match[3] ?? 0);
            if (isset($match[5]))
            {
                $accuracy = (int) $match[5];
            }
            else
            {
                $accuracy = 0;
            }
            $unsigned = isset($match['unsigned']);

            return true;
        }
        else
        {
            $typeName = '';
            $length = 0;
            $accuracy = 0;
            $unsigned = false;

            return false;
        }
    }

    /**
     * 渲染模版.
     */
    private function renderTemplate(string $template, array $data): string
    {
        extract($data);
        ob_start();
        include __DIR__ . '/' . $template . '.tpl';

        return ob_get_clean();
    }

    public const DB_FIELD_TYPE_MAP = [
        'int'       => ['int|null', '?int', '(int)'],
        'smallint'  => ['int|null', '?int', '(int)'],
        'tinyint'   => ['int|null', '?int', '(int)'],
        'mediumint' => ['int|null', '?int', '(int)'],
        'bigint'    => ['int|null', '?int', '(int)'],
        'bit'       => ['bool|null', '?bool', '(bool)'],
        'year'      => ['int|null', '?int', '(int)'],
        'double'    => ['float|null', '?float', '(float)'],
        'float'     => ['float|null', '?float', '(float)'],
        'decimal'   => ['string|float|int|null', \PHP_VERSION_ID >= 80000 ? 'string|float|int|null' : '', ''],
        'json'      => ['\\' . \Imi\Util\LazyArrayObject::class . '|object|array|null', '', ''],
        'set'       => ['array|null', '?array', ''],
    ];

    /**
     * 数据库字段类型转PHP的字段类型.
     *
     * 返回格式：[显示类型, 定义类型, 强制类型转换]
     */
    private function dbFieldTypeToPhp(string $type): array
    {
        return self::DB_FIELD_TYPE_MAP[explode(' ', $type)[0]] ?? ['string|null', '?string', '(string)'];
    }

    /**
     * 处理字段默认值
     *
     * @param mixed $default
     *
     * @return mixed
     */
    private function parseFieldDefaultValue(string $type, $default)
    {
        if (null === $default)
        {
            return null;
        }
        switch ($type)
        {
            case 'int':
            case 'smallint':
            case 'tinyint':
            case 'mediumint':
            case 'bigint':
            case 'year':
                return (int) $default;
            case 'bit':
                return (bool) str_replace(['b', '\''], '', $default);
            case 'double':
            case 'float':
                return (float) $default;
            case 'char':
            case 'varchar':
            case 'binary':
            case 'varbinary':
            case 'tinyblob':
            case 'blob':
            case 'mediumblob':
            case 'longblob':
            case 'text':
            case 'mediumtext':
            case 'enum':
                return (string) $default;
            case 'set':
                if ('' === $default)
                {
                    return null;
                }

                return explode(',', $default);
            default:
                return null;
        }
    }

    /**
     * 获取创建表的 DDL.
     */
    public function getDDL(IQuery $query, string $table, string $database): string
    {
        $result = $query->execute('show create table `' . $database . '`.`' . $table . '`');
        $row = $result->get();
        $sql = $row['Create Table'] ?? $row['Create View'] ?? '';

        return preg_replace('/ AUTO_INCREMENT=\d+ /', ' ', $sql, 1);
    }
}
