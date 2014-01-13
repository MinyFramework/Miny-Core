<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

require_once __DIR__ . '/../AutoLoader.php';

use ArrayAccess;
use InvalidArgumentException;
use Miny\AutoLoader;
use Miny\Factory\Factory;
use UnexpectedValueException;

abstract class BaseApplication implements ArrayAccess
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
     * @param string $directory
     * @param int $environment
     * @param boolean $include_configs
     */
    public function __construct($directory, $environment = self::ENV_PROD, $include_configs = true)
    {
        $this->environment = $environment;
        $autoloader        = new AutoLoader(array(
            '\Application' => $directory,
            '\Miny'        => __DIR__ . '/..',
            '\Modules'     => __DIR__ . '/../../Modules'
        ));
        $this->factory     = new Factory(array(
            'default_timezone' => 'UTC',
            'root'             => $directory,
            'log'              => array(
                'path'  => $directory . '/logs',
                'debug' => $this->isDeveloperEnvironment()
            ),
        ));
        $this->autoloader  = $autoloader;
        $this->setDefaultParameters();
        if ($include_configs) {
            $this->loadConfigFiles($directory);
        }
        $this->registerDefaultServices();
        $env = $this->isProductionEnvironment() ? 'production' : 'development';
        $this->log->info('Starting Miny in %s environment', $env);

        if (isset($this['modules']) && is_array($this['modules'])) {
            $module_handler = $this->module_handler;
            foreach ($this['modules'] as $module => $parameters) {
                if (is_numeric($module) && !is_array($parameters)) {
                    $module     = $parameters;
                    $parameters = array();
                }
                $module_handler->module($module, $parameters);
            }
        }
    }

    /**
     * @param string $directory
     */
    private function loadConfigFiles($directory)
    {
        $config_files = array(
            '/config/config.common.php' => self::ENV_COMMON,
            '/config/config.dev.php'    => self::ENV_DEV,
            '/config/config.test.php'   => self::ENV_TEST,
            '/config/config.php'        => self::ENV_PROD
        );
        foreach ($config_files as $file => $env) {
            try {
                $this->loadConfig($directory . $file, $env);
            } catch (InvalidArgumentException $e) {

            }
        }
    }

    /**
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    protected function setDefaultParameters()
    {

    }

    /**
     * @param string $file
     * @param int $env
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
        $this->getParameters()->addParameters($config);
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

    protected function registerDefaultServices()
    {
        $this->factory->setInstance('app', $this);
        $this->factory->add('log', '\Miny\Log')
                ->setArguments('@log:path', '@log:debug');
        $this->factory->add('error_handlers', '\Miny\Application\ErrorHandlers')
                ->setArguments('&log');
        $this->factory->add('events', '\Miny\Event\EventDispatcher')
                ->addMethodCall('register', 'uncaught_exception', '*error_handlers::logException');
        $this->factory->add('module_handler', '\Miny\Modules\ModuleHandler')
                ->setArguments('&app', '&log');
    }

    /**
     * Runs the application.
     */
    public function run()
    {
        $event = $this->factory->events;

        date_default_timezone_set($this['default_timezone']);
        $event->raiseEvent('before_run');
        register_shutdown_function(function () use ($event) {
            $event->raiseEvent('shutdown');
        });
        $this->onRun();
    }

    abstract protected function onRun();

    /* Magic methods are dispatched to Factory */

    public function __set($key, $value)
    {
        $this->factory->__set($key, $value);
    }

    public function __get($key)
    {
        return $this->factory->__get($key);
    }

    public function __call($method, $args)
    {
        return call_user_func_array(array($this->factory, $method), $args);
    }
    /* ArrayAccess interface */

    public function offsetExists($offset)
    {
        return $this->factory->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->factory->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->factory->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->factory->offsetUnset($offset);
    }
}
