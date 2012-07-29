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
    public $file;
    private $vars = array();
    private $filters = array();
    private $default_filters = array();
    private $view;

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
        $vars = array();
        foreach ($this->vars as $key => $data) {
            foreach ($this->filters[$key] as $filter) {
                $data = $this->view->filter($data, $filter);
            }
            $vars[$key] = $data;
        }
        return $vars;
    }

    public function render($format = NULL)
    {
        return $this->view->render($this->file, $format, $this->getVars());
    }

}