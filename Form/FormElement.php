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
 * @package   Miny/Form
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Form;

use \Miny\Form\Exceptions\FormElementExeption;

abstract class FormElement
{
    private $options;
    private $value;
    private $label;

    public function __construct($name, $label, array $options = array())
    {
        $this->label = $label;
        $this->options['name'] = $name;
        foreach ($options as $option => $val) {
            $this->options[$option] = $val;
        }
    }

    public function __set($key, $value)
    {
        if ($key == 'value') {
            $this->value = $value;
        } else {
            $this->options[$key] = $value;
        }
    }

    public function __get($key)
    {
        if (in_array($key, array('value', 'label', 'options'))) {
            return $this->$key;
        }
        if (!isset($this->options[$key])) {
            throw new \OutOfBoundsException('Option not set: ' . $key);
        }
        return $this->options[$key];
    }

    public function getHTMLArgList(array $args)
    {
        $arglist = '';
        foreach ($args as $name => $value) {
            $arglist .= sprintf(' %s="%s"', $name, $value);
        }
        return $arglist;
    }

    public function hasValue()
    {
        return !is_null($this->value);
    }

    public function renderLabel(array $options = array())
    {
        if (is_null($this->label)) {
            return NULL;
        }

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->options['name'];
        }

        $options['for'] = $this->options['id'];
        $options = $this->getHTMLArgList($options);
        return sprintf('<label%s>%s</label>', $options, $this->label);
    }

    public abstract function render(array $options = array());
}