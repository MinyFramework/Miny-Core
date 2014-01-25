<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Modules;

use Miny\Application\BaseApplication;
use Miny\Log;
use Miny\Modules\Exceptions\BadModuleException;

class ModuleHandler
{
    private static $module_class_format = '\Modules\%s\Module';

    /**
     * @var Module[]
     */
    private $modules = array();

    /**
     * @var BaseApplication
     */
    private $application;

    /**
     * @var Log
     */
    private $log;

    /**
     * @param BaseApplication $app
     * @param Log $log
     */
    public function __construct(BaseApplication $app, Log $log)
    {
        $this->application = $app;
        $this->log         = $log;

        $app->getFactory()->getBlueprint('events')
                ->addMethodCall('register', 'before_run', array($this, 'processConditionalRunnables'))
                ->addMethodCall('register', 'before_run', array($this, 'registerEventHandlers'));
    }

    private function log()
    {
        $args    = func_get_args();
        $message = array_shift($args);
        $this->log->debug('ModuleHandler: ' . $message, $args);
    }

    public function initialize()
    {
        foreach ($this->modules as $module) {
            $module->init($this->application);
        }
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

        $this->log('Loading module: %s', $module);
        $class        = sprintf(self::$module_class_format, $module);
        $module_class = new $class($this->application);
        if (!$module_class instanceof Module) {
            throw new BadModuleException(sprintf('Module descriptor %s does not extend Module class.', $class));
        }
        $this->modules[$module] = $module_class;
        foreach ($module_class->getDependencies() as $name) {
            $this->module($name);
        }
    }

    public function processConditionalRunnables()
    {
        foreach ($this->modules as $module) {
            foreach ($module->getConditionalRunnables() as $module_name => $runnable) {
                if (isset($this->modules[$module_name])) {
                    $runnable($this->application);
                }
            }
        }
    }

    public function registerEventHandlers()
    {
        $factory = $this->application->getFactory();
        $events  = $factory->get('events');
        foreach ($this->modules as $module) {
            foreach ($module->eventHandlers() as $event_name => $handler) {
                if (is_array($handler) && !is_callable($handler)) {
                    foreach ($handler as $callback) {
                        $events->register($event_name, $callback);
                    }
                } else {
                    $events->register($event_name, $handler);
                }
            }
        }
    }
}
