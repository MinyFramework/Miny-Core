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
 * @package   Miny/View
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0-dev
 */

namespace Miny\View;

use OutOfBoundsException;

class ViewDescriptor
{
    public $file;
    private $vars = array();
    //private $filters = array();
    //private $default_filters = array();
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
        $this->blocks[$name] = $this->renderBlock($name, $content);
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