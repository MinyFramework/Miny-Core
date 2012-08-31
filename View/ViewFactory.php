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
    protected $prefix = 'views/';
    protected $suffix = '';
    protected $helpers;
    protected $assigns = array();
    protected $views = array();

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

    public function setPrefix($prefix)
    {
        if (!is_string($prefix)) {
            throw new InvalidArgumentException('Format must be a string!');
        }
        $this->prefix = $prefix;
    }

    public function setSuffix($suffix)
    {
        if (!is_string($suffix)) {
            throw new InvalidArgumentException('Format must be a string!');
        }
        $this->suffix = $suffix;
    }

    public function setHelpers(ViewHelpers $helpers)
    {
        $this->helpers = $helpers;
    }

    public function __set($key, $value)
    {
        $this->assigns[$key] = $value;
    }

    public function __get($key)
    {
        if (!isset($this->assigns[$key])) {
            throw new OutOfBoundsException('Key not set: ' . $key);
        }
        return $this->assigns[$key];
    }

    public function __isset($key)
    {
        return isset($this->assigns[$key]);
    }

    public function __unset($key)
    {
        unset($this->assigns[$key]);
    }

    public function get($type, $template)
    {
        if (!isset($this->views[$type])) {
            throw new InvalidArgumentException('Unknown view type ' . $type);
        }
        $class = $this->views[$type];
        $view = new $class($this->prefix . $template . $this->suffix);
        if (!is_null($this->helpers)) {
            $view->setHelpers($this->helpers);
        }
        foreach ($this->assigns as $key => $value) {
            $view->$key = $value;
        }
        return $view;
    }

}