<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Modules;

use Miny\Application\BaseApplication;
use Miny\CoreEvents;
use Miny\Event\EventDispatcher;
use Miny\Log\Log;
use Miny\Modules\Exceptions\BadModuleException;

class ModuleHandler
{
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
     * @var EventDispatcher
     */
    private $events;

    /**
     * @param BaseApplication $app
     * @param EventDispatcher $events
     * @param Log             $log
     */
    public function __construct(
        BaseApplication $app,
        EventDispatcher $events,
        Log $log
    ) {
        $this->application = $app;
        $this->log         = $log;
        $this->events      = $events;

        $events->registerHandlers(
            CoreEvents::BEFORE_RUN,
            array(
                array($this, 'processConditionalCallbacks'),
                array($this, 'registerEventHandlers')
            )
        );
    }

    public function initialize()
    {
        foreach ($this->modules as $module) {
            $module->init($this->application);
        }
    }

    /**
     * @param array $configuration
     *
     * @return $this
     */
    public function loadModules(array $configuration)
    {
        foreach ($configuration as $module) {
            $this->module($module);
        }

        return $this;
    }

    /**
     * @param string $module
     *
     * @throws BadModuleException
     */
    public function module($module)
    {
        if (isset($this->modules[$module])) {
            return;
        }

        $this->log->write(Log::DEBUG, 'ModuleHandler', 'Loading module: %s', $module);

        $class        = "\\Modules\\{$module}\\Module";
        $moduleObject = new $class($module, $this->application);

        if (!$moduleObject instanceof Module) {
            throw new BadModuleException("Class {$class} does not extend Module class.");
        }

        $this->modules[$module] = $moduleObject;
        foreach ($moduleObject->getDependencies() as $name) {
            $this->module($name);
        }
    }

    public function processConditionalCallbacks()
    {
        foreach ($this->modules as $module) {
            foreach ($module->getConditionalCallbacks() as $module_name => $runnable) {
                if (isset($this->modules[$module_name])) {
                    $runnable($this->application);
                }
            }
        }
    }

    public function registerEventHandlers()
    {
        foreach ($this->modules as $module) {
            foreach ($module->eventHandlers() as $event_name => $handler) {
                if (is_array($handler) && !is_callable($handler)) {
                    foreach ($handler as $callback) {
                        $this->events->register($event_name, $callback);
                    }
                } else {
                    $this->events->register($event_name, $handler);
                }
            }
        }
    }
}
