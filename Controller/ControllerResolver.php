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
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class ControllerResolver
{
    /**
     * @var ControllerCollection
     */
    private $collection;

    public function __construct(ControllerCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param string $class
     * @param string $action
     * @param Request $request
     * @param Response $response
     * @throws InvalidArgumentException
     */
    public function resolve($class, $action, Request $request, Response $response)
    {
        $controller = $this->collection->getController($class);
        if ($controller instanceof Controller) {
            $controller->run($action, $request, $response);
        } elseif ($controller instanceof Closure) {
            $controller($request, $action, $response);
        } else {
            throw new InvalidArgumentException('Invalid controller: ' . $class);
        }
    }

}
