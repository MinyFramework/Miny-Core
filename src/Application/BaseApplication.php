<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Application\Events\BeforeRunEvent;
use Miny\Application\Events\ShutDownEvent;
use Miny\Application\Handlers\ErrorHandlers;
use Miny\AutoLoader;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\Factory\LinkResolver;
use Miny\Factory\ParameterContainer;
use Miny\Log\FileWriter;
use Miny\Log\Log;
use Miny\Shutdown\ShutdownService;
use Miny\TemporaryFiles\TemporaryFileManager;

abstract class BaseApplication
{
    const ENV_PROD   = 1;
    const ENV_DEV    = 2;
    const ENV_TEST   = 4;
    const ENV_COMMON = 7;

    /**
     * @var int
     */
    protected $environment;

    /**
     * @var ParameterContainer
     */
    protected $parameterContainer;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var Log
     */
    protected $log;

    /**
     * @param int        $environment
     * @param AutoLoader $autoLoader
     */
    public function __construct($environment = self::ENV_PROD, AutoLoader $autoLoader = null)
    {
        $environmentNames = [
            self::ENV_PROD => 'production',
            self::ENV_DEV  => 'development',
            self::ENV_TEST => 'testing'
        ];
        if (!isset($environmentNames[$environment])) {
            $this->environment = self::ENV_PROD;
        } else {
            $this->environment = $environment;
        }

        if ($autoLoader === null) {
            $autoLoader = new AutoLoader([
                '\\Application' => '.',
                '\\Modules'     => './vendor/miny/Modules'
            ]);
        }

        $root               = realpath('.');
        $parameterContainer = new ParameterContainer([
            'default_timezone' => 'UTC',
            'root'             => $root,
            'cache_directory'  => $root . '/cache',
            'profile'          => $this->isDeveloperEnvironment(),
            'log'              => [
                'enable_file_writer' => true,
                'path'               => $root . '/logs',
                'flush_limit'        => 100
            ],
        ]);

        $ioc = new Container(new LinkResolver($parameterContainer));
        $ioc->setInstance($autoLoader);
        $ioc->setInstance($parameterContainer);

        /** @var $log Log */
        $this->log = $ioc->get('Miny\\Log\\Log');

        if ($this->environment !== $environment) {
            $message = 'Unknown environment option "%s". Assuming production environment.';
            $this->log->write(Log::WARNING, 'Miny', $message, $environment);
        }
        $this->log->write(
            Log::INFO,
            'Miny',
            'Starting Miny in %s environment',
            $environmentNames[$this->environment]
        );

        $this->parameterContainer = $parameterContainer;
        $this->container          = $ioc;

        $this->setDefaultParameters($parameterContainer);
        $this->loadConfigFiles();
        $this->registerDefaultServices($ioc);

        $this->loadModules($parameterContainer);
    }

    private function loadModules(ParameterContainer $parameterContainer)
    {
        if (isset($parameterContainer['modules']) && is_array($parameterContainer['modules'])) {
            $this->container->get('Miny\\Modules\\ModuleHandler')
                ->loadModules($parameterContainer['modules'])
                ->initialize();
        }
    }

    /**
     * @param ParameterContainer $parameterContainer
     */
    protected function setDefaultParameters(ParameterContainer $parameterContainer)
    {

    }

    private function loadConfigFiles()
    {
        $config_files = [
            './config/config.common.php' => self::ENV_COMMON,
            './config/config.dev.php'    => self::ENV_DEV,
            './config/config.test.php'   => self::ENV_TEST,
            './config/config.php'        => self::ENV_PROD
        ];
        foreach ($config_files as $file => $env) {
            $this->loadConfigFile($file, $env, true);
        }
    }

    /**
     * @param string $file
     * @param int    $env
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function loadConfig($file, $env = self::ENV_COMMON)
    {
        $this->loadConfigFile($file, $env, false);
    }

    private function loadConfigFile($file, $env, $ignoreMissing = true)
    {
        if (!$this->isEnvironment($env)) {
            return;
        }
        if (!is_file($file)) {
            if ($ignoreMissing) {
                return;
            }
            throw new \InvalidArgumentException("Configuration file not found: {$file}");
        }
        $this->log->write(
            Log::DEBUG,
            'Configuration',
            'Loading configuration file: %s',
            $file
        );
        $this->parameterContainer->addParameters(
            include_file($file)
        );
    }

    protected function registerDefaultServices(Container $container)
    {
        $parameterContainer = $this->parameterContainer;

        date_default_timezone_set($parameterContainer['default_timezone']);

        $container->setInstance($this);
        $container->addAlias('Miny\\Log\\AbstractLog', 'Miny\\Log\\Log');

        /**
         * @var $shutdown      ShutdownService
         * @var $errorHandlers ErrorHandlers
         */
        $shutdown      = $container->get('Miny\\Shutdown\\ShutdownService');
        $errorHandlers = $container->get('Miny\\Application\\Handlers\\ErrorHandlers');

        $this->eventDispatcher = $container->get('Miny\\Event\\EventDispatcher');

        set_error_handler([$errorHandlers, 'handleErrors']);
        set_exception_handler([$errorHandlers, 'handleExceptions']);

        $this->log->setFlushLimit($parameterContainer['log']['flush_limit']);
        if ($parameterContainer['log']['enable_file_writer']) {
            $this->log->registerWriter(new FileWriter($parameterContainer['log']['path']));
        }

        $container->addAlias(
            'Miny\\TemporaryFiles\\TemporaryFileManager',
            function () use ($parameterContainer){
                return new TemporaryFileManager($parameterContainer['cache_directory']);
            }
        );

        $shutdown->register(
            function () {
                $this->eventDispatcher->raiseEvent(new ShutDownEvent());
            },
            0
        );
        $shutdown->register(
            function () {
                $this->log->write(Log::INFO, 'Miny', "End of execution.\n");
                $this->log->flush();
            },
            1000
        );

        if ($parameterContainer['profile']) {
            $profiler = $this->log->startProfiling('Miny', 'Application execution');
            $shutdown->register([$profiler, 'stop'], 998);
        }
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return ParameterContainer
     */
    public function getParameterContainer()
    {
        return $this->parameterContainer;
    }

    /**
     * @return int
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Checks whether the given $env matches the current environment.
     *
     * @param int $env
     *
     * @return boolean
     */
    public function isEnvironment($env)
    {
        return ($this->environment & $env) !== 0;
    }

    /**
     * @return boolean
     */
    public function isDeveloperEnvironment()
    {
        return $this->isEnvironment(self::ENV_DEV);
    }

    /**
     * @return boolean
     */
    public function isProductionEnvironment()
    {
        return $this->isEnvironment(self::ENV_PROD);
    }

    /**
     * @return boolean
     */
    public function isTestEnvironment()
    {
        return $this->isEnvironment(self::ENV_TEST);
    }

    /**
     * Runs the application.
     */
    public function run()
    {
        $this->eventDispatcher->raiseEvent(new BeforeRunEvent());
        $this->onRun();
    }

    abstract protected function onRun();
}

function include_file($file) {
    /** @noinspection PhpIncludeInspection */
    return include $file;
}