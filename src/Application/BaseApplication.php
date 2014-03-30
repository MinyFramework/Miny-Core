<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use InvalidArgumentException;
use Miny\Application\Events\BeforeRunEvent;
use Miny\Application\Events\ShutDownEvent;
use Miny\AutoLoader;
use Miny\CoreEvents;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\Factory\LinkResolver;
use Miny\Factory\ParameterContainer;
use Miny\Log\FileWriter;
use Miny\Log\Log;
use Miny\Shutdown\ShutdownService;
use UnexpectedValueException;

abstract class BaseApplication
{
    const ENV_PROD   = 1;
    const ENV_DEV    = 2;
    const ENV_TEST   = 4;
    const ENV_COMMON = 7;

    /**
     * @var ParameterContainer
     */
    private $parameterContainer;

    /**
     * @var int
     */
    private $environment;

    /**
     * @var Container
     */
    private $container;

    /**
     *
     * @param int             $environment
     * @param AutoLoader|null $autoLoader
     */
    public function __construct($environment = self::ENV_PROD, AutoLoader $autoLoader = null)
    {
        $environmentNames = array(
            self::ENV_PROD => 'production',
            self::ENV_DEV  => 'development',
            self::ENV_TEST => 'testing'
        );
        if (!isset($environmentNames[$environment])) {
            $this->environment = self::ENV_PROD;
        } else {
            $this->environment = $environment;
        }

        if ($autoLoader === null) {
            $autoLoader = new AutoLoader(array(
                '\Application' => '.',
                '\Modules'     => './vendor/miny/Modules'
            ));
        }
        $parameterContainer = new ParameterContainer(array(
            'default_timezone' => 'UTC',
            'root'             => realpath('.'),
            'profile'          => $this->isDeveloperEnvironment(),
            'log'              => array(
                'enable_file_writer' => true,
                'path'               => realpath('./logs'),
                'flush_limit'        => 100
            ),
        ));

        $ioc = new Container(new LinkResolver($parameterContainer));
        $ioc->setInstance($autoLoader);
        $ioc->setInstance($parameterContainer);

        /** @var $log Log */
        $log = $ioc->get('\\Miny\\Log\\Log');
        if ($this->environment !== $environment) {
            $message = 'Unknown environment option "%s". Assuming production environment.';
            $log->write(Log::WARNING, 'Miny', $message, $environment);
        }
        $log->write(
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
        if (!isset($parameterContainer['modules']) || !is_array($parameterContainer['modules'])) {
            return;
        }
        $this->container->get('\\Miny\\Modules\\ModuleHandler')
            ->loadModules($parameterContainer['modules'])
            ->initialize();
    }

    /**
     * @param ParameterContainer $parameterContainer
     */
    protected function setDefaultParameters(ParameterContainer $parameterContainer)
    {

    }

    private function loadConfigFiles()
    {
        $config_files = array(
            './config/config.common.php' => self::ENV_COMMON,
            './config/config.dev.php'    => self::ENV_DEV,
            './config/config.test.php'   => self::ENV_TEST,
            './config/config.php'        => self::ENV_PROD
        );
        foreach ($config_files as $file => $env) {
            try {
                $this->loadConfig($file, $env);
            } catch (InvalidArgumentException $e) {

            }
        }
    }

    /**
     * @param string $file
     * @param int    $env
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function loadConfig($file, $env = self::ENV_COMMON)
    {
        if (!$this->isEnvironment($env)) {
            return;
        }
        if (!is_file($file)) {
            throw new InvalidArgumentException('Configuration file not found: ' . $file);
        }
        $this->container->get('\\Miny\\Log\\Log')->write(
            Log::DEBUG,
            'Configuration',
            'Loading configuration file: %s',
            $file
        );
        $config = include $file;
        if (!is_array($config)) {
            throw new UnexpectedValueException('Invalid configuration file: ' . $file);
        }
        $this->parameterContainer->addParameters($config);
    }

    protected function registerDefaultServices(Container $container)
    {
        date_default_timezone_set($this->parameterContainer['default_timezone']);
        $container->setInstance($this);

        /** @var $shutdown ShutdownService */
        $shutdown = $container->get('\\Miny\\Shutdown\\ShutdownService');

        /** @var $log Log */
        $log = $container->get('\\Miny\\Log\\Log');

        $log->setFlushLimit($this->parameterContainer['log']['flush_limit']);
        if ($this->parameterContainer['log']['enable_file_writer']) {
            $log->registerWriter(new FileWriter($this->parameterContainer['log']['path']));
        }
        $shutdown->register(
            function () use ($log) {
                $log->write(Log::INFO, 'Miny', "End of execution.\n");
            },
            999
        );
        $shutdown->register(array($log, 'flush'), 1000);

        if ($this->parameterContainer['profile']) {
            $profiler = $log->startProfiling('Miny', 'Application execution');
            $shutdown->register(array($profiler, 'stop'), 998);
        }

        $errorHandlers = $container->get('\\Miny\\Application\\Handlers\\ErrorHandlers');

        $events = $container->get('\\Miny\\Event\\EventDispatcher');
        $events->register(CoreEvents::UNCAUGHT_EXCEPTION, array($errorHandlers, 'logException'));
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
        /** @var $event EventDispatcher */
        $event = $this->container->get('\\Miny\\Event\\EventDispatcher');

        /** @var $shutdown ShutdownService */
        $shutdown = $this->container->get('\\Miny\\Shutdown\\ShutdownService');

        $event->raiseEvent(new BeforeRunEvent());
        $shutdown->register(
            function () use ($event) {
                $event->raiseEvent(new ShutDownEvent());
            },
            0
        );
        $this->onRun();
    }

    abstract protected function onRun();
}
