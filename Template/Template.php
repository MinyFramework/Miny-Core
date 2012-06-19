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
    private $plugins;

    public function __construct($template_path, $default_format = NULL) {
        $this->path = $template_path;
        $this->setFormat($default_format);
    }

    public function plugin($key, $plugin = NULL) {
        if (is_null($plugin)) {
            if (!isset($this->plugins[$key])) {
                throw new \InvalidArgumentException('Plugin not found: ' . $key);
            }
            return $this->plugins[$key];
        } else {
            if (!is_callable($plugin) && !$plugin instanceof \Closure) {
                throw new \InvalidArgumentException('Invalid plugin: ' . $key);
            }
            $this->plugins[$key] = $plugin;
        }
    }

    public function __call($key, $args) {
        try {
            $plugin = $this->plugin($key);
            return call_user_func_array($plugin, $args);
        } catch (\InvalidArgumentException $e) {
            throw new \BadMethodCallException('Method not found: ' . $key, 0, $e);
        }
    }

    public function __set($key, $value) {
        $this->template_vars[$key] = $value;
    }

    public function clean() {
        $this->template_vars = array();
    }

    public function setFormat($format) {
        $this->format = $format;
    }

    public function getFormat($format = NULL) {
        if (is_null($format)) {
            if (is_null($this->format)) {
                return '';
            } else {
                return '.' . $this->format;
            }
        } else {
            return '.' . $format;
        }
    }

    public function render($template, $format = NULL) {
        if (!is_file($this->path . '/' . $template . '.' . $this->format . '.php')) {
            throw new \RuntimeException('Template not found: ' . $template . '.' . $this->format);
        }
        ob_start();
        extract($this->template_vars, EXTR_OVERWRITE);
        include $this->path . '/' . $template . $this->getFormat($format) . '.php';
        return ob_get_clean();
    }

}