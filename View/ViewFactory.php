<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

use InvalidArgumentException;
use OutOfBoundsException;

class ViewFactory
{
    /**
     * @var string
     */
    protected $directory = 'views';

    /**
     * @var string
     */
    protected $suffix = '';

    /**
     * @var ViewHelpers
     */
    protected $helpers;

    /**
     * @var array
     */
    protected $assigns = array();

    /**
     * @var string[]
     */
    protected $views = array();

    /**
     * @param string $type
     * @param string $class
     * @throws InvalidArgumentException
     */
    public function addViewType($type, $class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Nonexistent class set for view type ' . $type);
        }
        if (!is_subclass_of($class, __NAMESPACE__ . '\iTemplatingView')) {
            throw new InvalidArgumentException('Invalid class set for view type ' . $type);
        }
        $this->views[$type] = $class;
    }

    /**
     * @param string $dir
     * @throws InvalidArgumentException
     */
    public function setDirectory($dir)
    {
        if (!is_string($dir)) {
            throw new InvalidArgumentException('View directory must be a string!');
        }
        $this->directory = $dir;
    }

    /**
     * @param string $suffix
     * @throws InvalidArgumentException
     */
    public function setSuffix($suffix)
    {
        if (!is_string($suffix)) {
            throw new InvalidArgumentException('Format must be a string!');
        }
        $this->suffix = $suffix;
    }

    /**
     * @param ViewHelpers $helpers
     */
    public function setHelpers(ViewHelpers $helpers)
    {
        $this->helpers = $helpers;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->assigns[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws OutOfBoundsException
     */
    public function __get($key)
    {
        if (!isset($this->assigns[$key])) {
            throw new OutOfBoundsException('Key not set: ' . $key);
        }
        return $this->assigns[$key];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->assigns[$key]);
    }

    /**
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->assigns[$key]);
    }

    /**
     * @param string $type
     * @param string $template
     * @return iView
     * @throws InvalidArgumentException
     */
    public function get($type, $template)
    {
        if (!isset($this->views[$type])) {
            throw new InvalidArgumentException('Unknown view type ' . $type);
        }
        $class = $this->views[$type];
        $view = new $class($this->directory, $template . $this->suffix);
        if ($this->helpers !== NULL) {
            $view->setHelpers($this->helpers);
        }
        foreach ($this->assigns as $key => $value) {
            $view->$key = $value;
        }
        return $view;
    }

}
