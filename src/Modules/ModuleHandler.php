<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Modules;

use Miny\Application\BaseApplication;
use Miny\CoreEvents;
use Miny\Event\EventDispatcher;
use Miny\Log\AbstractLog;
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
     * @var AbstractLog
     */
    private $log;

    /**
     * @var EventDispatcher
     */
    private $events;

    /**
     * @param BaseApplication $app
     * @param EventDispatcher $events
     * @param AbstractLog     $log
     */
    public function __construct(BaseApplication $app, EventDispatcher $events, AbstractLog $log)
    {
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
            foreach ($module->getConditionalCallbacks() as $moduleName => $runnable) {
                if (isset($this->modules[$moduleName])) {
                    $runnable($this->application);
                }
            }
        }
    }

    public function registerEventHandlers()
    {
        foreach ($this->modules as $module) {
            foreach ($module->eventHandlers() as $eventName => $handlers) {
                $this->events->registerHandlers($eventName, $handlers);
            }
        }
    }
}
