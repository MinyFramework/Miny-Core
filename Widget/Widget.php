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
 * @package   Miny/Widget
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Widget;

abstract class Widget implements iWidget {

    private $assigns = array();
    private $services = array();
    private $container;
    public $view;

    public function setContainer(WidgetContainer $container) {
        $this->container = $container;
    }

    public function __set($key, $value) {
        $this->assigns[$key] = $value;
    }

    public function getAssigns() {
        return $this->assigns;
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

    public function begin(array $params = array()) {
        return $this;
    }

    public function end(array $params = array()) {
        return $this->container->render($this, $params);
    }

}