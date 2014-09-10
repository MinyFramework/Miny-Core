<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Modules;

use Miny\Application\BaseApplication;
use Miny\Factory\AbstractConfigurationTree;
use Miny\TemporaryFiles\TemporaryFileManager;
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
    private $conditionalCallbacks = [];

    /**
     * @var AbstractConfigurationTree
     */
    private $configuration;

    /**
     * @var TemporaryFileManager
     */
    private $fileManager;

    /**
     * @param string               $name
     * @param BaseApplication      $app
     *
     * @param TemporaryFileManager $fileManager
     */
    public function __construct($name, BaseApplication $app, TemporaryFileManager $fileManager)
    {
        $this->application  = $app;
        $this->fileManager  = $fileManager;
        $parameterContainer = $app->getParameterContainer();
        $parameterContainer->addParameters([$name => $this->defaultConfiguration()], false);
        $this->configuration = $parameterContainer->getSubTree($name);
    }

    public function getFileManager()
    {
        return $this->fileManager;
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
        if (isset($this->configuration)) {
            return $this->configuration[$key];
        }
        if ($default === null) {
            throw new OutOfBoundsException("Key {$key} is not set");
        }

        return $default;
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
        return [];
    }

    public function defaultConfiguration()
    {
        return [];
    }

    public function getConditionalCallbacks()
    {
        return $this->conditionalCallbacks;
    }

    public function eventHandlers()
    {
        return [];
    }

    public function ifModule($module, callable $callback)
    {
        $this->conditionalCallbacks[$module] = $callback;
    }

    abstract public function init(BaseApplication $app);
}
