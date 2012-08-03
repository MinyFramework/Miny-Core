<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

use Miny\Event\Event;
use Miny\Event\EventHandler;
use Miny\Routing\Exceptions\PageNotFoundException;

class RouteFilter extends EventHandler
{
    private $router;
    private $handled_exceptions = array();

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function addExceptionHandler($exception_class, $redirect_to)
    {
        $this->handled_exceptions[$exception_class] = $redirect_to;
    }

    public function filterRoutes(Event $event)
    {
        $request = $event->getParameter('request');
        $match = $this->router->match($request->path, $request->method);
        if (!$match) {
            $message = 'Page not found: ' . $request->path;
            throw new PageNotFoundException($message);
        }
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_GET);
        $request->get = $match->getParameters() + $_GET;
    }

    public function handleRequestException(Event $event)
    {
        $request = $event->getParameter('request');
        if ($request->isSubRequest()) {
            return;
        }
        $ex = $event->getParameter('exception');
        $class = get_class($ex);
        if (!isset($this->handled_exceptions[$class])) {
            throw $ex;
        }
        $request->path = $this->handled_exceptions[$class];
    }

}