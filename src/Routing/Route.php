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
    const PARAMETER_PATTERN      = '/:(\w+)/';

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
    private $parameterNames = array();

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
    private $parameterCount;

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
        if (!isset($this->parameterCount)) {
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
        $this->path           = $path;
        $this->parameterCount = null;
        $this->parameterNames = array();
        $this->regex          = null;
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
        $this->patterns[$parameter] = $pattern;
    }

    /**
     * @param string $parameter
     * @param string $default
     *
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
        if (!isset($this->parameterCount)) {
            $this->build();
        }

        return $this->parameterCount;
    }

    /**
     * @return array
     */
    public function getParameterNames()
    {
        if (!isset($this->parameterCount)) {
            $this->build();
        }

        return $this->parameterNames;
    }

    private function addParameter($matches)
    {
        ++$this->parameterCount;
        $this->parameterNames[] = $matches[1];
        if (!isset($matches[2])) {
            return $matches[0];
        }
        $this->specify($matches[1], '(' . $matches[2] . ')');

        return ':' . $matches[1];
    }

    private function build()
    {
        $this->parameterCount = 0;
        $this->extractPatterns();
        if ($this->parameterCount === 0) {
            return;
        }
        $this->regex = preg_quote($this->path, '#');
        foreach ($this->parameterNames as $name) {
            $this->regex = str_replace(
                '\:' . $name,
                $this->getPattern($name),
                $this->regex
            );
        }
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
