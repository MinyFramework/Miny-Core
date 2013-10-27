<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

use BadMethodCallException;
use InvalidArgumentException;

class ViewFactory extends ViewBase
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
        if (!is_subclass_of($class, __NAMESPACE__ . '\iView')) {
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
     * @param string $type
     * @param array $args
     * @return iView
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    public function __call($type, $args)
    {
        if (count($args) == 0) {
            throw new BadMethodCallException('This method requires a template argument.');
        }
        $template = $args[0];
        if (!is_string($template)) {
            throw new BadMethodCallException('The template argument must be a string.');
        }
        if (!isset($this->views[$type])) {
            throw new InvalidArgumentException('Unknown view type ' . $type);
        }
        $class = $this->views[$type];
        $view = new $class($this->directory, $template . $this->suffix);
        if ($this->helpers !== NULL) {
            $view->setHelpers($this->helpers);
        }
        $view->setVariables($this->variables);
        return $view;
    }

}
