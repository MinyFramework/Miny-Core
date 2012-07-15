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

class Route
{
    private $path;
    private $parameters;
    private $parameter_names = array();
    private $patterns = array();
    private $regex;
    private $parameter_count;

    public function __construct($path, $method = NULL, array $params = array())
    {
        $this->method = $method;
        $this->path = $path;
        $this->parameters = $params;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Path must be a string.');
        }
        $this->path = $path;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function addParameters(array $parameters)
    {
        $this->parameters = $this->parameters + $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function specify($parameter, $pattern)
    {
        $this->patterns[$parameter] = $pattern;
    }

    public function getPattern($parameter, $default = '(\w+)')
    {
        if (isset($this->patterns[$parameter])) {
            return $this->patterns[$parameter];
        }
        return $default;
    }

    public function isStatic()
    {
        return $this->getParameterCount() === 0;
    }

    public function getRegex()
    {
        if ($this->isStatic()) {
            return false;
        }
        return $this->regex;
    }

    public function getParameterCount()
    {
        if (is_null($this->parameter_count)) {
            $this->build();
        }
        return $this->parameter_count;
    }

    public function getParameterNames()
    {
        if (is_null($this->parameter_count)) {
            $this->build();
        }
        return $this->parameter_names;
    }

    public function getParameterName($key)
    {
        if (is_null($this->parameter_count)) {
            $this->build();
        }
        if (!isset($this->parameter_names[$key])) {
            $message = 'Parameter name not set for kes: ' . $key;
            throw new \UnexpectedValueException($message);
        }
        return $this->parameter_names[$key];
    }

    private function build()
    {
        $arr = array();
        $this->parameter_count = preg_match_all('/:(\w+)/', $this->path, $arr);
        if ($this->parameter_count === 0) {
            return;
        }
        $this->parameter_names = $arr[1];
        $tokens = array();
        foreach ($arr[1] as $k => $name) {
            $tokens[$arr[0][$k]] = $this->getPattern($name);
        }
        $this->regex = str_replace(array('#', '?'), array('\#', '\?'),
                $this->path);
        $this->regex = str_replace(array_keys($tokens), $tokens, $this->regex);
    }

}