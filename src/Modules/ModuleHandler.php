<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Modules;

use Miny\Application\BaseApplication;
use Miny\Application\CoreEvents;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\Log\Log;
use Miny\Modules\Exceptions\BadModuleException;

class ModuleHandler
{
    private static $module_class_format = '\Modules\%s\Module';

    /**
     * @var Module[]
     */
    private $modules;

    /**
     * @var string[]
     */
    private $loaded;

    /**
     * @var BaseApplication
     */
    private $application;

    /**
     * @var Log
     */
    private $log;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var EventDispatcher
     */
    private $events;

    /**
     * @param BaseApplication $app
     * @param Container       $container
     * @param EventDispatcher $events
     * @param Log             $log
     */
    public function __construct(
        BaseApplication $app,
        Container $container,
        EventDispatcher $events,
        Log $log
    ) {
        $this->application = $app;
        $this->log         = $log;
        $this->container   = $container;
        $this->events      = $events;

        $this->loaded  = array();
        $this->modules = array();

        $events->register(CoreEvents::BEFORE_RUN, array($this, 'processConditionalCallbacks'));
        $events->register(CoreEvents::BEFORE_RUN, array($this, 'registerEventHandlers'));
    }

    /**
     * @param array $configuration
     *
     * @return $this
     */
    public function loadModules(array $configuration)
    {
        foreach ($configuration as $module => $parameters) {
            if (is_int($module) && !is_array($parameters)) {
                $module     = $parameters;
                $parameters = array();
            }
            $this->module($module, $parameters);
        }

        return $this;
    }

    public function initialize()
    {
        foreach ($this->modules as $module) {
            $module->init($this->application);
        }
    }

    /**
     * @param string $module
     *
     * @throws BadModuleException
     */
    public function module($module)
    {
        if (in_array($module, $this->loaded)) {
            return;
        }
        $this->loaded[] = $module;

        $this->log->write(Log::DEBUG, 'ModuleHandler', 'Loading module: %s', $module);
        $class        = sprintf(self::$module_class_format, $module);
        $module_class = $this->container->get($class);
        if (!$module_class instanceof Module) {
            $message = sprintf('Module descriptor %s does not extend Module class.', $class);
            throw new BadModuleException($message);
        }
        foreach ($module_class->getDependencies() as $name) {
            $this->module($name);
        }
        $this->modules[$module] = $module_class;
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
