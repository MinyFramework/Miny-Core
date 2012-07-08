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
 * @package   Miny/Controller
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\Controller;

use \Miny\HTTP\Request;

abstract class Controller
{
    private $default_action;
    private $assigns = array();
    private $services = array();
    private $headers = array();
    private $cookies = array();

    /**
     * HTTP status code
     * @var int
     */
    public $status = 200;

    /**
     * The template file to render
     * @var string
     */
    public $template;

    public function __construct($default_action = NULL)
    {
        $this->default_action = $default_action;
    }

    public function __set($key, $value)
    {
        $this->assign($key, $value);
    }

    public function assign($key, $value, $scope = NULL)
    {
        $this->assigns[$key] = array($value, $scope);
    }

    public function service($key, $service)
    {
        $this->services[$key] = $service;
    }

    public function __get($key)
    {
        if (isset($this->services[$key])) {
            return $this->services[$key];
        } elseif (array_key_exists($key, $this->assigns)) {
            if (is_null($this->assigns[$key][1])) {
                //Only get variables assigned to current scope.
                return $this->assigns[$key][0];
            }
        }
        throw new \OutOfBoundsException('Variable not set: ' . $key);
    }

    public function cookie($name, $value)
    {
        $this->cookies[$name] = $value;
    }

    public function header($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function getCookies()
    {
        return $this->cookies;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getAssigns()
    {
        return $this->assigns;
    }

    public function request($path, array $get = NULL, array $post = NULL,
                            array $cookie = NULL)
    {
        $request = new Request($path, $get, $post, $cookie, Request::SUB_REQUEST);
        //TODO: biztosítani kell, hogy ez egyáltalán létezzen
        $response = $this->dispatcher->dispatch($request);

        foreach ($response->getHeaders() as $name => $value) {
            $this->header($name, $value);
        }

        foreach ($response->getCookies() as $name => $value) {
            $this->cookie($name, $value);
        }

        return $response;
    }

    public function run($controller, $action, Request $request)
    {
        if (!$action) {
            $action = $this->default_action ? : 'index';
        }
        $fn = $action . 'Action';
        if (!method_exists($this, $fn)) {
            throw new \InvalidArgumentException('Action not found: ' . $fn);
        }
        $this->template = $controller . '/' . $action;

        return $this->$fn($request);
    }

}