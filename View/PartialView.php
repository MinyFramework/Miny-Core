<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

class PartialView implements iView
{
    protected $template_string;
    protected $vars = array();

    public function __construct($template_string)
    {
        if (!is_string($template_string)) {
            throw new \InvalidArgumentException('Partial template must be string!');
        }
        $this->template_string = $template_string;
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

    public function __isset($key)
    {
        return isset($this->vars[$key]);
    }

    public function __unset($key)
    {
        unset($this->vars[$key]);
    }

    protected function replacePlaceholder($matches)
    {
        $key = $matches[1];
        if (!isset($this->vars[$key])) {
            return $key;
        }
        return (string) $this->vars[$key];
    }

    public function render()
    {
        return preg_replace_callback('/(?<!\\\)[(\w+?)(?<!\\\)]/mu', 'self::replacePlaceholder', $this->template_string);
    }

}