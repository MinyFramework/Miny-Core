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

class View extends ViewBase implements iView
{
    protected $fragment_stack = array();
    protected $fragments = array();
    protected $directory;
    protected $helpers;
    protected $template;
    protected $extend;
    protected $variable_stack = array();

    public function __construct($directory, $template)
    {
        $this->directory = $directory;
        $this->setTemplate($template);
    }

    public function setHelpers(ViewHelpers $helpers)
    {
        $this->helpers = $helpers;
    }

    public function setVariables(array $variables)
    {
        $this->variables = $variables;
    }

    public function saveVariables()
    {
        $this->variable_stack[] = $this->variables;
    }

    public function pushVariables(array $variables)
    {
        $this->saveVariables();
        $this->setVariables($variables);
    }

    public function restoreVariables()
    {
        if (empty($this->variables)) {
            throw new BadMethodCallException('Unable to restore: There are no saved variables.');
        }
        $this->variables = array_pop($this->variable_stack);
    }

    public function __isset($key)
    {
        return parent::__isset($key) || isset($this->fragments[$key]);
    }

    public function __get($key)
    {
        if (!$this->__isset($key)) {
            throw new OutOfBoundsException('Key not set: ' . $key);
        }
        return $this->get($key, '', true);
    }

    public function __call($method, $arguments)
    {
        if ($this->helpers === NULL) {
            throw new BadMethodCallException('Helper functions are not set.');
        }
        return call_user_func_array(array($this->helpers, $method), $arguments);
    }

    public function get($key, $default = '', $escape = true)
    {
        if (isset($this->fragments[$key])) {
            return $this->fragments[$key][0];
        }

        $var = parent::__isset($key) ? parent::__get($key) : $default;

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

    public function render(array $variables = NULL)
    {
        if (isset($variables)) {
            $this->pushVariables($variables);
        }
        $file = $this->template;
        do {
            $this->extend = NULL;
            $content = $this->renderFile($file);
        } while ($file = $this->extend);

        if (isset($variables)) {
            $this->restoreVariables();
        }
        return $content;
    }

    public function __toString()
    {
        return $this->render();
    }

}
