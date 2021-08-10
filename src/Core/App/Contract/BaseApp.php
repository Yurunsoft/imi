<?php

declare(strict_types=1);

namespace Imi\Core\App\Contract;

use Imi\App;
use Imi\AppContexts;
use Imi\Config;
use Imi\Config\DotEnv\DotEnv;
use Imi\Core\Runtime\Handler\DefaultRuntimeModeHandler;
use Imi\Core\Runtime\Runtime;
use Imi\Event\Event;
use Imi\Main\Helper;

abstract class BaseApp implements IApp
{
    /**
     * 命名空间.
     */
    protected string $namespace = '';

    /**
     * 应用模式的配置.
     */
    protected array $appConfig = [];

    /**
     * 构造方法.
     */
    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * 加载配置.
     */
    public function loadConfig(bool $initDotEnv = true): void
    {
        // 加载框架配置
        Config::addConfig('@imi', include \dirname(IMI_PATH) . '/config/config.php');

        $appPath = App::get(AppContexts::APP_PATH);
        $hasAppConfig = false;
        // 加载项目目录下的 env
        DotEnv::load([$appPath]);
        $fileName = $appPath . '/config/config.php';
        if (is_file($fileName))
        {
            Config::addConfig('@app', include $fileName);
            $hasAppConfig = true;
        }
        if (!$hasAppConfig)
        {
            Config::setConfig('@app', []);
        }

        $appConfig = Config::get('@app');
        App::setDebug($appConfig['debug'] ?? true);
        if ($initDotEnv)
        {
            $this->loadDotEnv();
        }

        // 应用模式配置
        $appModeConfig = $appConfig['imi'] ?? [];
        $appTypeConfig = $appConfig[$this->getType()]['imi'] ?? [];
        Config::set('@app.imi', array_merge($this->appConfig, $appModeConfig, $appTypeConfig));
        Config::set('@app.imi.beans', array_merge($this->appConfig['beans'] ?? [], $appModeConfig['beans'] ?? [], $appTypeConfig['beans'] ?? []));
    }

    /**
     * 加载入口.
     */
    public function loadMain(): void
    {
        $this->initLogger();

        if (!Helper::getMain('Imi', 'imi'))
        {
            throw new \RuntimeException('Framework imi must have the class Imi\\Main');
        }

        Helper::getMain($this->namespace, 'app');
        Event::trigger('IMI.INIT_MAIN');
    }

    protected function loadDotEnv(): void
    {
        if ($_ENV)
        {
            // 加载 .env 配置
            foreach ($_ENV as $name => $value)
            {
                Config::set($name, $value);
            }
        }
    }

    /**
     * 初始化.
     */
    public function init(): void
    {
        if ($beanBinds = Config::get('@app.imi.beans'))
        {
            App::getContainer()->setBinds($beanBinds);
        }
    }

    /**
     * 初始化运行时.
     */
    protected function initRuntime(): void
    {
        Runtime::setRuntimeModeHandler(DefaultRuntimeModeHandler::class)->init();
    }

    /**
     * 初始化日志.
     */
    protected function initLogger(): void
    {
        $config = Config::get('@app.logger.channels.imi');
        if (null === $config)
        {
            Config::set('@app.logger.channels.imi', [
                'handlers' => [],
            ]);
        }
    }
}
