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
use OutOfBoundsException;

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
     * @param string          $name
     * @param BaseApplication $app
     *
     * @throws Exceptions\BadModuleException
     */
    public function __construct($name, BaseApplication $app)
    {
        $this->application  = $app;
        $parameterContainer = $app->getParameterContainer();
        $parameterContainer->addParameters(array($name => $this->defaultConfiguration()), false);
        $this->configuration = $parameterContainer->getSubTree($name);
    }

    /**
     * @return AbstractConfigurationTree
     */
    public function getConfigurationTree()
    {
        return $this->configuration;
    }

    public function getConfiguration($key, $default = null)
    {
        if (!isset($this->configuration)) {
            if ($default === null) {
                throw new OutOfBoundsException("Key {$key} is not set");
            }

            return $default;
        }

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

    public function ifModule($module, $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('$callback must be callable.');
        }
        $this->conditionalCallbacks[$module] = $callback;
    }

    abstract public function init(BaseApplication $app);
}
