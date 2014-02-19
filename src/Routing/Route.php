<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

use InvalidArgumentException;
use Miny\Routing\Exceptions\BadMethodException;

class Route
{
    private static $methods = array('GET', 'POST', 'PUT', 'DELETE');
    const PARAMETER_WITH_PATTERN = '/:(\w+)(?:\((.*?)\))?/';

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string[]
     */
    private $parameterNames;

    /**
     * @var string[]
     */
    private $patterns = array();

    /**
     * @var string
     */
    private $regex;

    /**
     * @param string $path
     * @param string $method
     * @param array  $params
     *
     * @throws BadMethodException
     */
    public function __construct($path, $method = null, array $params = array())
    {
        if ($method !== null) {
            $method = strtoupper($method);
            if (!in_array($method, self::$methods)) {
                throw new BadMethodException('Unexpected route method: ' . $method);
            }
        }
        $this->setPath($path);
        $this->method     = $method;
        $this->parameters = $params;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if (!isset($this->parameterNames)) {
            $this->build();
        }

        return $this->path;
    }

    /**
     * @param string $path
     *
     * @throws InvalidArgumentException
     */
    public function setPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Path must be a string.');
        }
        $this->path = $path;
        unset($this->parameterNames);
        unset($this->regex);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    public function isMethod($method)
    {
        if ($method === null) {
            return true;
        }
        if ($this->method === null) {
            return true;
        }

        return $method === $this->method;
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
        $this->patterns['\:' . $parameter] = $pattern;
    }

    /**
     * @param string $parameter
     * @param string $default
     *
     * @return string
     */
    public function getPattern($parameter, $default = '(\w+)')
    {
        if (isset($this->patterns['\:' . $parameter])) {
            return $this->patterns['\:' . $parameter];
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
        return $this->path;
    }

    /**
     * @return int
     */
    public function getParameterCount()
    {
        if (!isset($this->parameterNames)) {
            $this->build();
        }

        return count($this->parameterNames);
    }

    /**
     * @return array
     */
    public function getParameterNames()
    {
        if (!isset($this->parameterNames)) {
            $this->build();
        }

        return $this->parameterNames;
    }

    private function addParameter($matches)
    {
        $this->parameterNames[] = $matches[1];
        if (isset($matches[2])) {
            $pattern = $matches[2];
            $retVal  = ':' . $matches[1];
        } else {
            $pattern = '\w+';
            $retVal  = $matches[0];
        }
        $key = '\:' . $matches[1];
        if (!isset($this->patterns[$key])) {
            $this->patterns[$key] = '(' . $pattern . ')';
        }

        return $retVal;
    }

    private function build()
    {
        $this->parameterNames = array();
        $this->extractPatterns();
        if ($this->isStatic()) {
            return;
        }
        $this->regex = preg_quote($this->path, '#');
        $this->regex = str_replace(array_keys($this->patterns), $this->patterns, $this->regex);
    }

    private function extractPatterns()
    {
        $this->path = preg_replace_callback(
            self::PARAMETER_WITH_PATTERN,
            array($this, 'addParameter'),
            $this->path
        );
    }
}
