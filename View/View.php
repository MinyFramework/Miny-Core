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

use Miny\Extendable;
use RuntimeException;

class View extends Extendable
{
    public $default_filters = array();
    private $path;
    private $format;
    private $descriptors = array();
    private $blocks = array();
    private $block_stack = array();

    public function __construct($path, $format = NULL)
    {
        $this->path = $path;
        $this->format = $format;
    }

    public function get($name = NULL, $file = NULL)
    {
        if (!$name) {
            return new ViewDescriptor($file, $this, $this->default_filters);
        }
        if (!isset($this->descriptors[$name])) {
            $this->descriptors[$name] = new ViewDescriptor($file, $this, $this->default_filters);
        }
        return $this->descriptors[$name];
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function getFormat($format = NULL)
    {
        $format = $format ? : $this->format;
        if ($format) {
            return '.' . $format;
        }
    }

    public function filter($data, $filter)
    {
        $method = 'filter_' . $filter;
        return $this->$method($data);
    }

    private function getTemplatePath($template, $format)
    {
        $filename = $template . $this->getFormat($format);
        $file = $this->path . '/' . $filename . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Template not found: ' . $filename);
        }
        return $file;
    }

    public function beginBlock($name)
    {
        ob_start();
        $this->block_stack[] = $name;
    }

    public function endBlock()
    {
        $name = array_pop($this->block_stack);
        $this->blocks[$name] = ob_get_clean();
    }

    public function block($name, $default = '')
    {
        return isset($this->blocks[$name]) ? $this->blocks[$name] : $default;
    }

    public function render($file, $format = NULL, array $params = array())
    {
        ob_start();
        extract($params, EXTR_SKIP);
        include $this->getTemplatePath($file, $format);
        return ob_get_clean();
    }

}