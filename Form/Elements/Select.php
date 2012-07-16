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
 * @package   Miny/Form/Elements
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Form\Elements;

use \Miny\Form\FormElement;

class Select extends FormElement
{
    protected $choices = array();
    protected $multiple = false;

    public function __construct($name, $label, array $choices, array $options = array())
    {
        if (isset($options['multiple'])) {
            $options['multiple'] = 'multiple';
            $name .= '[]';
            $this->multiple = true;
        }
        $this->choices = $choices;
        parent::__construct($name, $label, $options);
    }

    public function render(array $args = array())
    {
        $args = $args + $this->options;
        return sprintf('<select%s>%s</select>', $this->getHTMLArgList($args), $this->renderChoices());
    }

    protected function renderChoices()
    {
        $options = '';
        $data = $this->value;
        if(is_null($data) && $this->multiple) {
            $data = array();
        }
        foreach ($this->choices as $key => $text) {
            if ($this->multiple) {
                $selected = in_array($key, $data) ? ' selected="selected"' : '';
            } else {
                $selected = $data == $key ? ' selected="selected"' : '';
            }
            $options .= sprintf('<option value="%s"%s>%s</option>', $key, $selected, $text);
        }
        return $options;
    }

}