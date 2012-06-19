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

abstract class Controller implements iController {

    private $default_action;
    private $status = 200;
    private $assigns = array();
    private $headers = array(); //$http = array();
    private $cookies = array();
    private $services = array();
    private $template;

    public function __construct($default_action = NULL) {
        $this->default_action = $default_action;
    }

    public function __set($key, $value) {
        $this->assigns[$key] = $value;
    }

    public function service($key, $service) {
        $this->services[$key] = $service;
    }

    public function __get($key) {
        if (isset($this->services[$key])) {
            return $this->services[$key];
        } else {
            if (array_key_exists($key, $this->assigns)) {
                return $this->assigns[$key];
            }
            throw new \OutOfBoundsException('Variable not set: ' . $key);
        }
    }

    public function status($code = NULL) {
        if ($code) {
            $this->status = $code;
        } else {
            return $this->status;
        }
    }

    public function cookie($name, $value) {
        $this->cookies[$name] = $value;
    }

    public function header($name, $value) {
        $this->headers[$name] = $value;
    }

    public function getCookies() {
        return $this->cookies;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getAssigns() {
        return $this->assigns;
    }

    public function setTemplate($template) {
        $this->template = $template;
    }

    public function getTemplate() {
        return $this->template;
    }

    public function request($path, array $get = array(), array $post = array()) {
        $request = new \Miny\HTTP\Request($path, $get, $post, \Miny\HTTP\Request::SUB_REQUEST);
        $response = $this->dispatcher->dispatch($request); //TODO: biztosítani kell, hogy ez egyáltalán létezzen - System::Event?

        foreach ($response->getHeaders() as $name => $value) {
            $this->header($name, $value);
        }

        foreach ($response->getCookies() as $name => $value) {
            $this->cookie($name, $value);
        }

        return $response;
    }

    public function run($controller, $action, array $params = NULL) {
        if (!$action) {
            $action = $this->default_action ? : 'index';
        }
        $fn = $action . 'Action';
        if (!method_exists($this, $fn)) {
            throw new \InvalidArgumentException('Action not found: ' . $fn);
        }
        $this->setTemplate($controller . '/' . $action);

        return $this->$fn($params);
    }

}