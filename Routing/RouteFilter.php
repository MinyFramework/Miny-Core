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
            $str = substr($_SERVER['REQUEST_URI'], $start + 1);
            parse_str($str, $extra);
        }
        $request->get(NULL, $route->get() + $_GET + $extra);
        $event->setResponse($request);
    }

}