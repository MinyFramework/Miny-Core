<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use InvalidArgumentException;
use Miny\AutoLoader;
use Miny\Factory\Factory;
use Miny\Shutdown\ShutdownService;
use UnexpectedValueException;

abstract class BaseApplication
{
    const ENV_PROD   = 1;
    const ENV_DEV    = 2;
    const ENV_TEST   = 4;
    const ENV_COMMON = 7;

    /**
     * @var int
     */
    private $environment;

    /**
     * @var Factory
     */
    private $factory;

    /**
     *
     * @param int             $environment
     * @param AutoLoader|null $autoloader
     */
    public function __construct($environment = self::ENV_PROD, AutoLoader $autoloader = null)
    {
        $environment_names = array(
            self::ENV_PROD => 'production',
            self::ENV_DEV  => 'development',
            self::ENV_TEST => 'testing'
        );
        if (!isset($environment_names[$environment])) {
            $warning     = 'Unknown environment option "' . $environment . '". Assuming production environment.';
            $environment = self::ENV_PROD;
        }
        $this->environment = $environment;

        if ($autoloader === null) {
            $autoloader = new AutoLoader(array(
                '\Application' => '.',
                '\Modules'     => './vendor/miny/Modules'
            ));
        }
        $factory = new Factory(array(
            'default_timezone' => 'UTC',
            'root'             => realpath('.'),
            'log'              => array(
                'path'  => realpath('./logs'),
                'debug' => $this->isDeveloperEnvironment()
            ),
        ));
        $factory->setInstance('autoloader', $autoloader);
        $this->factory = $factory;

        $this->setDefaultParameters();
        $this->loadConfigFiles();
        $this->registerDefaultServices($factory);

        //Log start of execution
        if (isset($warning)) {
            $factory->get('log')->warning($warning);
        }
        $factory->get('log')->info('Starting Miny in %s environment', $environment_names[$environment]);

        //Load modules
        $parameters = $factory->getParameters();
        if (isset($parameters['modules']) && is_array($parameters['modules'])) {
            $module_handler = $factory->get('module_handler');
            foreach ($parameters['modules'] as $module => $parameters) {
                if (is_int($module) && !is_array($parameters)) {
                    $module     = $parameters;
                    $parameters = array();
                }
                $module_handler->module($module, $parameters);
            }
            $module_handler->initialize();
        }
    }

    /**
     * @return boolean
     */
    public function isDeveloperEnvironment()
    {
        return $this->isEnvironment(self::ENV_DEV);
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

    protected function setDefaultParameters()
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
        $config = include $file;
        if (!is_array($config)) {
            throw new UnexpectedValueException('Invalid configuration file: ' . $file);
        }
        $this->factory->getParameters()->addParameters($config);
    }

    protected function registerDefaultServices(Factory $factory)
    {
        $factory->setInstance('app', $this);
        $factory->add('log', '\Miny\Log')
            ->setArguments('@log:path', '@log:debug');
        $factory->add('error_handlers', '\Miny\Application\Handlers\ErrorHandlers')
            ->setArguments('&log');
        $factory->add('events', '\Miny\Event\EventDispatcher')
            ->addMethodCall('register', 'uncaught_exception', '*error_handlers::logException');
        $factory->add('module_handler', '\Miny\Modules\ModuleHandler')
            ->setArguments('&app', '&log');

        $shutdown = new ShutdownService();
        if (defined('START_TIME')) {
            $shutdown->register(function () use ($factory) {
                $log = $factory->get('log');
                $log->info('Execution time: %lf s', microtime(true) - START_TIME);
            }, 999);
        }
        $shutdown->register(function () use ($factory) {
            $log = $factory->get('log');
            $log->info("End of execution.\n");
            $log->saveLog();
        }, 1000);
    }

    /**
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @return int
     */
    public function getEnvironment()
    {
        return $this->environment;
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
        $event = $this->factory->get('events');

        date_default_timezone_set($this->factory['default_timezone']);
        $event->raiseEvent('before_run');
        register_shutdown_function(function () use ($event) {
            $event->raiseEvent('shutdown');
        });
        $this->onRun();
    }

    abstract protected function onRun();
}
