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

    public function renderBlock($name, $content = '', $mode = 'replace')
    {
        if (isset($this->blocks[$name])) {
            list($old_content, $old_mode) = $this->blocks[$name];

            if (is_null($old_mode)) {
                $old_mode = $mode;
            }

            switch ($old_mode) {
                case 'replace':
                    $content = $old_content;
                    break;
                case 'append':
                    $content .= $old_content;
                    break;
                case 'prepend':
                    $content = $old_content . $content;
                    break;
            }
        }
        return $content;
    }

    public function block($name, $content, $mode = NULL)
    {
        $content = $this->renderBlock($name, $content, NULL);
        $this->blocks[$name] = array($content, $mode);
        return $content;
    }

    public function beginBlock($name, $mode = NULL)
    {
        $this->block_stack[] = array($name, $mode);
        ob_start();
    }

    public function endBlock()
    {
        list($name, $mode) = array_pop($this->block_stack);
        return $this->block($name, ob_get_clean(), $mode);
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