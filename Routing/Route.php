<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

use InvalidArgumentException;

class Route
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string[]
     */
    private $parameter_names = array();

    /**
     * @var string[]
     */
    private $patterns = array();

    /**
     * @var string
     */
    private $regex;

    /**
     * @var int
     */
    private $parameter_count;

    /**
     * @param string $path
     * @param string $method
     * @param array $params
     */
    public function __construct($path, $method = NULL, array $params = array())
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Path must be a string');
        }
        $this->method     = $method;
        $this->path       = $path;
        $this->parameters = $params;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @throws InvalidArgumentException
     */
    public function setPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Path must be a string.');
        }
        $this->path            = $path;
        $this->parameter_count = NULL;
        $this->parameter_names = array();
        $this->regex           = NULL;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameters = $parameters + $this->parameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $parameter
     * @param string $pattern
     */
    public function specify($parameter, $pattern)
    {
        $this->patterns[$parameter] = $pattern;
    }

    /**
     * @param string $parameter
     * @param string $default
     * @return string
     */
    public function getPattern($parameter, $default = '(\w+)')
    {
        if (isset($this->patterns[$parameter])) {
            return $this->patterns[$parameter];
        }
        return $default;
    }

    /**
     * @return boolean
     */
    public function isStatic()
    {
        return $this->getParameterCount() === 0;
    }

    /**
     * @return string
     */
    public function getRegex()
    {
        if ($this->getParameterCount()) {
            return $this->regex;
        }
    }

    /**
     * @return int
     */
    public function getParameterCount()
    {
        if (is_null($this->parameter_count)) {
            $this->build();
        }
        return $this->parameter_count;
    }

    /**
     * @return array
     */
    public function getParameterNames()
    {
        if (is_null($this->parameter_count)) {
            $this->build();
        }
        return $this->parameter_names;
    }

    private function build()
    {
        $arr                   = array();
        $this->parameter_count = preg_match_all('/:(\w+)/', $this->path, $arr);
        if ($this->parameter_count === 0) {
            return;
        }
        $this->parameter_names = $arr[1];
        $tokens                = array();
        foreach ($arr[1] as $k => $name) {
            $tokens[$arr[0][$k]] = $this->getPattern($name);
        }
        $this->regex = str_replace(array('#', '?'), array('\#', '\?'), $this->path);
        $this->regex = str_replace(array_keys($tokens), $tokens, $this->regex);
    }
}
