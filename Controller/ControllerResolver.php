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
use Miny\HTTP\Request;

class ControllerResolver
{
    private $application;
    private $collection;

    public function __construct(Application $application, ControllerCollection $collection = NULL)
    {
        $this->application = $application;
        $this->collection = $collection;
    }

    public function resolve($class, $action, Request $request)
    {
        $controller = $this->collection->getController($class);
        if (is_string($controller)) {
            $controller = new $controller($this->application);
        } elseif (is_array($controller)) {
            if (isset($controller[0]) && is_callable($controller[0])) {
                $callback = array_shift($controller);
                $controller = call_user_func_array($callback, $controller);
            }
        }
        if ($controller instanceof Controller) {
            return $controller->run($action, $request);
        } elseif ($controller instanceof Closure) {
            return $controller($request, $action);
        }
        throw new InvalidArgumentException('Invalid controller: ' . $class);
    }

}