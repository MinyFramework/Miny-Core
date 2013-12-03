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
        parent::__construct(array(
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
        $this->log->info('Starting Miny in %s environment', $env);

        $module_handler = $this->module_handler;
        if (isset($this['modules']) && is_array($this['modules'])) {
            foreach ($this['modules'] as $module) {
                $module_handler->module($module);
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

    protected function registerDefaultServices()
    {
        $log = new Log($this['log']['path']);
        $log->setDebugMode($this['log']['debug']);
        $this->log = $log;

        $errh = new ErrorHandlers($log);
        $this->add('events', '\Miny\Event\EventDispatcher')
                ->addMethodCall('register', 'uncaught_exception', array($errh, 'logException'));
        $this->add('module_handler', '\Miny\Application\ModuleHandler')
                ->setArguments('&app', '&log');
    }

    public function run()
    {
        $event = $this->events;

        $event->raiseEvent('before_run');
        register_shutdown_function(function()use($event) {
            $event->raiseEvent('shutdown');
        });
        $this->onRun();
    }

    abstract public function onRun();
}
