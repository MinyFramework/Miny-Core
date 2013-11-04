<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

require_once __DIR__ . '/../AutoLoader.php';
require_once __DIR__ . '/../Factory/Factory.php';

use InvalidArgumentException;
use Miny\Application\Exceptions\BadModuleException;
use Miny\AutoLoader;
use Miny\Factory\Factory;
use Miny\Log;
use UnexpectedValueException;

abstract class BaseApplication extends Factory
{
    const ENV_PROD = 0;
    const ENV_DEV = 1;
    const ENV_COMMON = 2;

    /**
     * @var Module[]
     */
    private $modules = array();

    /**
     * @var int
     */
    private $environment;

    /**
     * @param string $directory
     * @param int $environment
     * @param boolean $include_configs
     */
    public function __construct($directory, $environment = self::ENV_PROD, $include_configs = true)
    {
        $this->environment = $environment;
        $this->autoloader = new AutoLoader(
                array(
            '\Application' => $directory,
            '\Miny'        => __DIR__ . '/..',
            '\Modules'     => __DIR__ . '/../../Modules'
        ));
        $this->setParameters(array(
            'default_timezone' => 'UTC',
            'root'             => $directory,
            'log'              => array(
                'path'  => $directory . '/logs',
                'debug' => $this->isDeveloperEnvironment()
            ),
        ));
        $this->setDefaultParameters();
        $this->setInstance('app', $this);
        if ($include_configs) {
            $this->loadConfigFiles($directory);
        }
        $this->registerDefaultServices();
        $env = $this->isProductionEnvironment() ? 'production' : 'development';
        $this->log->write(sprintf('Starting Miny in %s environment', $env));
        if (isset($this['modules']) && is_array($this['modules'])) {
            foreach ($this['modules'] as $module) {
                $this->module($module);
            }
        }
    }

    /**
     * @param string $directory
     */
    private function loadConfigFiles($directory)
    {
        $config_files = array(
            $directory . '/config/config.common.php' => self::ENV_COMMON,
            $directory . '/config/config.dev.php'    => self::ENV_DEV,
            $directory . '/config/config.php'        => self::ENV_PROD
        );
        foreach ($config_files as $file => $env) {
            try {
                $this->loadConfig($file, $env);
            } catch (InvalidArgumentException $e) {

            }
        }
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
        if ($env != $this->environment && $env != self::ENV_COMMON) {
            return;
        }
        if (!is_file($file)) {
            throw new InvalidArgumentException('Configuration file not found: ' . $file);
        }
        $config = include $file;
        if (!is_array($config)) {
            throw new UnexpectedValueException('Invalid configuration file: ' . $file);
        }
        $this->setParameters($config);
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
    public function isDeveloperEnvironment()
    {
        return $this->environment == self::ENV_DEV;
    }

    /**
     * @return boolean
     */
    public function isProductionEnvironment()
    {
        return $this->environment == self::ENV_PROD;
    }

    /**
     * @param string $module
     * @throws BadModuleException
     */
    public function module($module)
    {
        if (isset($this->modules[$module])) {
            return;
        }

        $this->log->write(sprintf('Loading Miny module: %s', $module), Log::DEBUG);
        $class = sprintf('\Modules\%s\Module', $module);
        if (!is_subclass_of($class, '\Miny\Application\Module')) {
            throw new BadModuleException('Module descriptor should extend Module class: ' . $class);
        }
        $module_class = new $class($this);
        $this->modules[$module] = $module_class;
        foreach ($module_class->getDependencies() as $name) {
            $this->module($name);
        }
        if (func_num_args() == 1) {
            $module_class->init($this);
        } else {
            $args = func_get_args();
            $args[0] = $this;
            call_user_func_array(array($module_class, 'init'), $args);
        }
    }

    protected function registerDefaultServices()
    {
        $log = new Log($this['log']['path']);
        $log->setDebugMode($this['log']['debug']);

        $errh = new ErrorHandlers($log);
        $this->add('events', '\Miny\Event\EventDispatcher')
                ->addMethodCall('register', 'uncaught_exception', array($errh, 'logException'));

        $this->log = $log;
    }

    /**
     *
     */
    abstract public function run();
}
