<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Modules;

use InvalidArgumentException;
use Miny\Application\BaseApplication;
use Miny\Factory\AbstractConfigurationTree;
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
    private $conditionalCallbacks = array();

    /**
     * @var AbstractConfigurationTree
     */
    private $configuration;

    /**
     * @param BaseApplication $app
     *
     * @throws BadModuleException
     */
    public function __construct($name, BaseApplication $app)
    {
        $this->application = $app;
        foreach ($this->includes() as $file) {
            if (!is_file($file)) {
                $message = sprintf('Required file not found: %s', $file);
                throw new BadModuleException($message);
            }
            include_once $file;
        }

        $parameterContainer = $app->getParameterContainer();
        $parameterContainer->addParameters(
            $this->defaultConfiguration(),
            false
        );
        $this->configuration = $parameterContainer->getSubTree($module);
    }

    public function getConfiguration($key)
    {
        return $this->configuration[$key];
    }

    public function setConfiguration($key, $value)
    {
        $this->configuration[$key] = $value;
    }

    public function hasConfiguration($key)
    {
        return isset($this->configuration[$key]);
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

    public function getConditionalCallbacks()
    {
        return $this->conditionalCallbacks;
    }

    public function eventHandlers()
    {
        return array();
    }

    public function ifModule($module, $runnable)
    {
        if (!is_callable($runnable)) {
            throw new InvalidArgumentException('Runnable must be a callable variable.');
        }
        $this->conditionalCallbacks[$module] = $runnable;
    }

    abstract public function init(BaseApplication $app);
}
