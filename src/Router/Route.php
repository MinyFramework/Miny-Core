<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Router;

use Miny\Router\Exceptions\BadMethodException;

class Route
{
    const METHOD_GET    = 1;
    const METHOD_POST   = 2;
    const METHOD_PUT    = 4;
    const METHOD_DELETE = 8;
    const METHOD_ALL    = 15;

    private $path;
    private $regexp;
    private $parameterPatterns;
    private $defaultValues;
    private $method;

    public function __construct()
    {
        $this->parameterPatterns = array();
        $this->defaultValues     = array();
    }

    public function specify($placeholder, $pattern)
    {
        $this->parameterPatterns[$placeholder] = $pattern;
    }

    public function isStatic()
    {
        return !isset($this->regexp);
    }

    /**
     * @return int
     */
    public function getParameterCount()
    {
        return count($this->getParameterPatterns());
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getParameterPatterns()
    {
        return $this->parameterPatterns;
    }

    public function getParameterNames()
    {
        return array_keys($this->parameterPatterns);
    }

    /**
     * @return mixed
     */
    public function getRegexp()
    {
        return $this->regexp;
    }

    /**
     * @param mixed $regexp
     */
    public function setRegexp($regexp)
    {
        $this->regexp = $regexp;
    }

    /**
     * @param mixed $method
     *
     * @throws BadMethodException
     */
    public function setMethod($method)
    {
        if ($method < 1 || $method > self::METHOD_ALL) {
            throw new BadMethodException('Invalid method mask ' . $method);
        }
        $this->method = $method;
    }

    /**
     * @param $method
     *
     * @return bool
     */
    public function isMethod($method)
    {
        return ($this->method & $method) !== 0;
    }

    /**
     * @return mixed
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return Route $this
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->defaultValues = $key + $this->defaultValues;
        } else {
            $this->defaultValues[$key] = $value;
        }

        return $this;
    }
}
