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

class Route implements iRoute
{
    private $name;
    private $path;
    private $default_parameters;
    private $matched_parameters = array();
    private $parameter_names = array();
    private $parameter_patterns = array();
    private $regex;
    private $static;

    public function __construct($path, $name = NULL, $method = NULL,
                                array $default_parameters = NULL)
    {
        $this->method = $method;
        $this->path = $path;
        $this->name = $name;
        $this->default_parameters = $default_parameters ? : array();
    }

    public function specify($parameter, $pattern)
    {
        $this->parameter_patterns[$parameter] = $pattern;
    }

    private function getParameterPattern($parameter)
    {
        if (isset($this->parameter_patterns[$parameter])) {
            return $this->parameter_patterns[$parameter];
        }
        return '(\w+)';
    }

    private function build()
    {
        if ($this->static !== NULL) {
            return;
        }
        $arr = array();
        if (!empty($this->path)) {
            $path = $this->path . '.:format';
        } else {
            $path = '';
        }
        preg_match_all('/:(\w+)/', $path, $arr);
        $this->parameter_names = $arr[1];
        $tokens = array();
        foreach ($arr[1] as $k => $name) {
            $tokens[$arr[0][$k]] = $this->getParameterPattern($name);
        }
        $this->regex = str_replace(array('#', '?'), array('\#', '\?'), $path);
        $this->regex = str_replace(array_keys($tokens), $tokens, $this->regex);
    }

    public function match($path, $method = NULL)
    {
        if ($method !== NULL && $this->method !== NULL) {
            if ($method !== $this->method) {
                return false;
            }
        }

        $this->build();
        $matched = array();
        if (preg_match('#^' . $this->regex . '$#Du', $path, $matched)) {
            unset($matched[0]);
            foreach ($matched as $k => $v) {
                $this->matched_parameters[$this->parameter_names[$k - 1]] = $v;
            }
            return $this;
        }
        return false;
    }

    public function get($parameter = NULL)
    {
        if ($parameter === NULL) {
            return $this->default_parameters + $this->matched_parameters;
        }
        if (!isset($this->default_parameters[$parameter])) {
            if (!isset($this->matched_parameters[$parameter])) {
                $message = 'Parameter not set: ' . $parameter;
                throw new \OutOfBoundsException($message);
            }
            return $this->matched_parameters[$parameter];
        }
        return $this->default_parameters[$parameter];
    }

    public function generate($name, array $parameters = array())
    {
        if ($this->name !== $name) {
            return false;
        }
        $this->build();
        foreach ($this->parameter_names as $param) {
            if (!array_key_exists($param, $parameters)) {
                $message = 'Parameter not set: ' . $param;
                throw new \InvalidArgumentException($message);
            }
        }
        return $this->path . '.:format';
    }

}