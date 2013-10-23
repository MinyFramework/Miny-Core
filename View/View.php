<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

use BadMethodCallException;
use OutOfBoundsException;
use UnexpectedValueException;

class View implements iView, iTemplatingView
{
    protected $fragment_stack = array();
    protected $fragments = array();
    protected $vars = array();
    protected $helpers;
    protected $template;
    protected $extend;

    public function __construct($directory, $template)
    {
        $this->directory = $directory;
        $this->setTemplate($template);
    }

    public function setHelpers(ViewHelpers $helpers)
    {
        $this->helpers = $helpers;
    }

    public function __call($method, $arguments)
    {
        if ($this->helpers === NULL) {
            throw new BadMethodCallException('Helper functions are not set.');
        }
        return call_user_func_array(array($this->helpers, $method), $arguments);
    }

    public function __set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function __get($key)
    {
        if (!$this->__isset($key)) {
            throw new OutOfBoundsException('Key not set: ' . $key);
        }
        return $this->get($key, '', true);
    }

    public function __isset($key)
    {
        return isset($this->vars[$key]) || isset($this->fragments[$key]);
    }

    public function __unset($key)
    {
        unset($this->vars[$key]);
    }

    public function get($key, $default = '', $escape = true)
    {
        if (isset($this->fragments[$key])) {
            return $this->fragments[$key][0];
        }

        if (!isset($this->vars[$key])) {
            $var = $default;
        } else {
            $var = $this->vars[$key];
        }

        if (is_callable($var)) {
            $var = call_user_func($var, $this);
        }

        if (is_string($var) && $escape) {
            $var = htmlspecialchars($var);
        }

        if ($var instanceof iView) {
            return $var->render();
        }
        return $var;
    }

    protected function getFileName($file)
    {
        return $this->directory . '/' . $file . '.php';
    }

    public function extend($extend)
    {
        if (!is_file($this->getFileName($extend))) {
            throw new UnexpectedValueException(sprintf('View file not found: %s.php', $extend));
        }
        $this->extend = $extend;
    }

    public function renderFragment($name, $content = '', $mode = 'replace')
    {
        if (isset($this->fragments[$name])) {
            list($old_content, $old_mode) = $this->fragments[$name];

            if ($old_mode === NULL) {
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

    public function fragment($name, $content, $mode = NULL)
    {
        $content = $this->renderFragment($name, $content, NULL);
        $this->fragments[$name] = array($content, $mode);
        return $content;
    }

    public function beginFragment($name, $mode = NULL)
    {
        $this->fragment_stack[] = array($name, $mode);
        ob_start();
    }

    public function endFragment()
    {
        if (empty($this->fragment_stack)) {
            throw new BadMethodCallException('Fragment stack is empty');
        }
        list($name, $mode) = array_pop($this->fragment_stack);
        return $this->fragment($name, ob_get_clean(), $mode);
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function renderFile($file)
    {
        ob_start();
        include $this->getFileName($file);
        return ob_get_clean();
    }

    public function render()
    {
        $file = $this->template;
        do {
            $this->extend = NULL;
            $content = $this->renderFile($file);
        } while ($file = $this->extend);
        return $content;
    }

}
