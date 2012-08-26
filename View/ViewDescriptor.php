<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\View;

use OutOfBoundsException;

class ViewDescriptor
{
    public $file;
    private $vars = array();
    private $view;
    private $blocks = array();
    private $block_modes = array();
    private $block_stack = array();
    private $extend;

    public function __construct($file, View $view)
    {
        $this->file = $file;
        $this->view = $view;
    }

    public function __set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function __get($key)
    {
        if (!isset($this->vars[$key])) {
            throw new OutOfBoundsException('Key not set: ' . $key);
        }
        return $this->vars[$key];
    }

    public function addVars(array $vars)
    {
        $this->vars = $vars + $this->vars;
    }

    public function getVars()
    {
        $vars = $this->vars;
        $vars['view'] = $this;
        return $vars;
    }

    public function extend($extend)
    {
        $this->extend = $extend;
    }

    public function renderBlock($name, $default = '')
    {
        if (isset($this->blocks[$name])) {
            if ($this->block_modes[$name] == 'append') {
                return $default . $this->blocks[$name];
            } else {
                return $this->blocks[$name];
            }
        } else {
            return $default;
        }
    }

    public function beginBlock($name, $mode = NULL)
    {
        ob_start();
        $this->block_stack[] = $name;
        if (!$mode) {
            if (!isset($this->block_modes[$name])) {
                $this->block_modes[$name] = 'replace';
            }
        } else {
            $this->block_modes[$name] = $mode;
        }
    }

    public function endBlock()
    {
        $content = ob_get_clean();
        $name = array_pop($this->block_stack);

        if (isset($this->blocks[$name]) && $this->block_modes[$name] == 'append') {
            $this->blocks[$name] = $content . $this->blocks[$name];
        } else {
            $this->blocks[$name] = $content;
        }

        return $this->blocks[$name];
    }

    public function render($format = NULL)
    {
        $file = $this->file;
        do {
            $this->extend = NULL;
            $content = $this->view->render($file, $format, $this->getVars());
        } while ($file = $this->extend);
        return $content;
    }

}