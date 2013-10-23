<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Closure;
use InvalidArgumentException;
use Miny\Application\Application;
use Miny\Controller\Controller;
use UnexpectedValueException;

class ControllerCollection
{
    /**
     * @var (Controller|Closure|string)[]
     */
    private $controllers = array();

    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @param string $name
     * @param (Controller|Closure|string) $controller
     * @throws InvalidArgumentException
     */
    public function register($name, $controller)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Controller name must be a string');
        }
        if (!$controller instanceof Closure && !$controller instanceof Controller && !is_string($controller)) {
            throw new InvalidArgumentException(sprintf('Invalid controller: %s (%s)', $name, gettype($controller)));
        }
        $this->controllers[$name] = $controller;
    }

    /**
     * @return string
     */
    public function getNextName()
    {
        return '_controller_' . count($this->controllers);
    }

    /**
     * @param string $class
     * @return Controller|Closure
     * @throws UnexpectedValueException
     */
    public function getController($class)
    {
        if (!is_string($class)) {
            throw new InvalidArgumentException('Controller name must be a string');
        }
        if (isset($this->controllers[$class])) {
            $class = $this->controllers[$class];
            if (!is_string($class)) {
                //In this case $class is either a Closure or a Controller
                return $class;
            }
        }
        if (!class_exists($class)) {
            $class = '\Application\Controllers\\' . ucfirst($class) . 'Controller';
            if (!class_exists($class)) {
                throw new UnexpectedValueException('Class not exists: ' . $class);
            }
        }
        if (!is_subclass_of($class, __NAMESPACE__ . '\Controller')) {
            throw new UnexpectedValueException('Class does not extend Controller: ' . $class);
        }
        return new $class($this->application);
    }

}
