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
 * @version   1.0
 */

namespace Miny\View;

use OutOfBoundsException;

class ViewDescriptor
{
    const APPEND_BLOCK = 0;
    const REPLACE_BLOCK = 1;
    const RENDER_NORMAL = 0;
    const RENDER_EXTENDED = 1;

    public $file;
    private $vars = array();
    private $filters = array();
    private $default_filters = array();
    private $view;
    private $blocks = array();
    private $block_modes = array();
    private $in_block;
    private $extend;
    private $state;

    public function __construct($file, View $view, array $default_filters = array())
    {
        $this->file = $file;
        $this->default_filters = $default_filters;
        $this->view = $view;
    }

    public function __set($key, $value)
    {
        $this->vars[$key] = $value;
        $this->filters[$key] = $this->default_filters;
    }

    public function __get($key)
    {
        $data = $this->getRaw($key);
        $filters = $this->filters[$key];
        foreach ($filters as $filter) {
            $data = $this->view->filter($data, $filter);
        }
        return $data;
    }

    public function getRaw($key)
    {
        if (!isset($this->vars[$key])) {
            throw new OutOfBoundsException('Key not set: ' . $key);
        }
        return $this->vars[$key];
    }

    public function getVars()
    {
        $vars = array('view' => $this);
        foreach ($this->vars as $key => $data) {
            foreach ($this->filters[$key] as $filter) {
                $data = $this->view->filter($data, $filter);
            }
            $vars[$key] = $data;
        }
        return $vars;
    }

    public function extend($extend)
    {
        $this->extend = $extend;
    }

    public function renderBlock($name, $default = '')
    {
        if ($this->state != self::RENDER_EXTENDED) {
            return;
        }
        if (isset($this->blocks[$name])) {
            $mode = $this->block_modes[$name];
            if ($mode == self::REPLACE_BLOCK) {
                return $this->blocks[$name];
            } else {
                return $default . $this->blocks[$name];
            }
        } else {
            return $default;
        }
    }

    public function beginBlock($name, $mode = self::REPLACE_BLOCK)
    {
        ob_start();
        $this->in_block = $name;
        $this->block_modes[$name] = $mode;
    }

    public function endBlock()
    {
        $name = $this->in_block;
        $this->in_block = NULL;
        $content = ob_get_clean();
        if ($this->state == self::RENDER_NORMAL) {
            $this->blocks[$name] = $content;
        } else {
            if (isset($this->blocks[$name])) {
                $mode = $this->block_modes[$name];
                if ($mode == self::REPLACE_BLOCK) {
                    return $this->blocks[$name];
                } else {
                    return $content . $this->blocks[$name];
                }
            } else {
                return $content;
            }
        }
    }

    public function render($format = NULL)
    {
        $this->state = self::RENDER_NORMAL;
        $content = $this->view->render($this->file, $format, $this->getVars());
        if ($this->extend) {
            $this->state = self::RENDER_EXTENDED;
            $content = $this->view->render($this->extend, $format, $this->getVars());
        }
        return $content;
    }

}