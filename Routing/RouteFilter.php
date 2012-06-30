<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Routing
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Routing;

use \Miny\Event\Event;
use \Miny\Event\EventHandler;

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
        $route = $this->router->match($request->path, $request->method);
        if (!$route) {
            $message = 'Page not found: ' . $request->path;
            throw new \HttpRequestException($message);
        }
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_GET);
        $request->get(NULL, $route->get() + $_GET);
    }

    public function handleRequestException(Event $event)
    {
        if (empty($this->handled_exceptions)) {
            return;
        }
        $ex = $event->getParameter('exception');
        $class = get_class($ex);
        if (!isset($this->handled_exceptions[$class])) {
            throw $ex;
        }
        $request = $event->getParameter('request');
        $request->path = $this->handled_exceptions[$class];
        $this->fitlerRoutes($event);
    }

}