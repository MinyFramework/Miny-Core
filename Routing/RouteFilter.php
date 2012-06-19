<?php

namespace Miny\Routing;

class RouteFilter implements \Miny\Event\iEventHandler {

    private $router;

    public function setRouter(\Miny\Routing\Router $router) {
        $this->router = $router;
    }

    public function handle(\Miny\Event\Event $event, $handling_method = NULL) {
        $request = $event->getParameter('request');
        $route = $this->router->match($request->path, $request->method);
        if (!$route) {
            throw new \RuntimeException('E404 - Page not found: ' . $request->path);
        }
        $start = strpos($_SERVER['REQUEST_URI'], '?');
        $extra = array();
        if ($start) {
            $str = substr($_SERVER['REQUEST_URI'], $start+1);
            parse_str($str, $extra);
        }
        $request->get(NULL, $route->get() + $_GET + $extra);
        $event->setResponse($request);
    }

}