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
    private $controllers = array();
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function register($name, $controller)
    {
        if (!$controller instanceof Closure && !$controller instanceof Controller && !is_string($controller)) {
            throw new InvalidArgumentException(sprintf('Invalid controller: %s (%s)', $name, gettype($controller)));
        }
        $this->controllers[$name] = $controller;
    }

    public function getNextName()
    {
        return '_controller_' . count($this->controllers);
    }

    public function getController($class)
    {
        if (isset($this->controllers[$class])) {
            $class = $this->controllers[$class];
            if (!is_string($class)) {
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