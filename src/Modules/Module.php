<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Modules;

use Closure;
use InvalidArgumentException;
use Miny\Application\BaseApplication;
use Miny\Modules\Exceptions\BadModuleException;

abstract class Module
{
    /**
     * @var BaseApplication
     */
    protected $application;

    /**
     * @var callback[]
     */
    private $conditional_runnables = array();

    /**
     * @param BaseApplication $app
     * @throws BadModuleException
     */
    public function __construct(BaseApplication $app)
    {
        $this->application = $app;
        foreach ($this->includes() as $file) {
            if (!is_file($file)) {
                throw new BadModuleException('Required file not found: ' . $file);
            }
            include_once $file;
        }
        $parameters = $app->getFactory()->getParameters();
        $parameters->addParameters($this->defaultConfiguration(), false);
    }

    public function getDependencies()
    {
        return array();
    }

    public function includes()
    {
        return array();
    }

    public function defaultConfiguration()
    {
        return array();
    }

    public function getConditionalRunnables()
    {
        return $this->conditional_runnables;
    }

    public function eventHandlers()
    {
        return array();
    }

    public function ifModule($module, $runnable)
    {
        if (!is_callable($runnable) || !$runnable instanceof Closure) {
            throw new InvalidArgumentException('Runnable must be a callable variable.');
        }
        $this->conditional_runnables[$module] = $runnable;
    }

    abstract public function init(BaseApplication $app);
}
