<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Template
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Template;

class Template {

    private $path;
    private $template_vars = array();
    private $format;
    private $plugins = array();

    public function __construct($path, $default_format = NULL) {
        $this->path = $path;
        $this->setFormat($default_format);
    }

    public function getPath() {
        return $this->path;
    }

    public function addPlugin($key, $plugin) {
        if (!is_callable($plugin) && !$plugin instanceof \Closure) {
            throw new \InvalidArgumentException('Invalid plugin: ' . $key);
        }
        $this->plugins[$key] = $plugin;
    }

    public function getPlugin($key) {
        if (!isset($this->plugins[$key])) {
            throw new \InvalidArgumentException('Plugin not found: ' . $key);
        }
        return $this->plugins[$key];
    }

    public function __call($key, $args) {
        try {
            $plugin = $this->getPlugin($key);
            return call_user_func_array($plugin, $args);
        } catch (\InvalidArgumentException $e) {
            throw new \BadMethodCallException('Method not found: ' . $key, 0, $e);
        }
    }

    public function __set($key, $value) {
        $this->assign($key, $value);
    }

    public function assign($key, $value) {
        $this->template_vars[$key] = $value;
    }

    public function clean() {
        $this->template_vars = array();
    }

    public function setFormat($format) {
        $this->format = $format;
    }

    public function getFormat($format = NULL) {
        $format = $format ? : $this->format;
        if (!is_null($format)) {
            $format = '.' . $format;
        }
        return $format;
    }

    private function getTemplatePath($template, $format) {
        $format = $this->getFormat($format);
        $file = $this->getPath() . '/' . $template . $format . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Template not found: ' . $template . $format);
        }
        return $file;
    }

    public function render($template, $format = NULL) {
        ob_start();
        extract($this->template_vars, EXTR_SKIP);
        include $this->getTemplatePath($template, $format);
        return ob_get_clean();
    }

}