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

class FormBuilder
{
    private $descriptor;
    private $error_renderer;
    private $errors_rendered = false;

    public function __construct(FormDescriptor $form = NULL,
                                iFormErrorRenderer $renderer = NULL)
    {
        if (is_null($form)) {
            $form = new FormDescriptor;
        }
        if (is_null($renderer)) {
            $renderer = new FormErrorRenderer;
        }
        $this->descriptor = $form;
        $this->error_renderer = $renderer;
    }

    public function addField(FormElement $field)
    {
        $this->descriptor->addField($field);
    }

    public function getHTMLArgList(array $args)
    {
        $arglist = '';
        foreach ($args as $name => $value) {
            $arglist .= sprintf(' %s="%s"', $name, $value);
        }
        return $arglist;
    }

    public function generate(array $options = array(), $errors_first = true,
                             $reset = false)
    {
        $form = $this->begin($options);
        if ($errors_first) {
            $form .= $this->errors();
        }
        foreach (array_keys($this->descriptor->getFields()) as $key) {
            $form .= $this->label($key);
            $form .= $this->render($key);
        }
        if ($reset) {
            $form .= $this->reset();
        }
        $form .= $this->submit();
        $form .= $this->end();
        return $form;
    }

    public function submit(array $options = array())
    {
        $options['type'] = 'submit';
        return sprintf('<input%s />', $this->getHTMLArgList($options));
    }

    public function reset(array $options = array())
    {
        $options['type'] = 'reset';
        return sprintf('<input%s />', $this->getHTMLArgList($options));
    }

    public function begin(array $options = array())
    {
        $method = $this->descriptor->getOption('method');

        $options['method'] = ($method == 'GET') ? 'GET' : 'POST';

        $form = sprintf('<form%s>', $this->getHTMLArgList($options));
        $hidden = '<input type="hidden" name="" value ="%s">' . "\n";
        if ($method != $options['method']) {
            $form .= sprintf($hidden, '_method', $method);
        }
        if ($this->descriptor->getOption('csrf')) {
            $form .= sprintf($hidden, 'token', $this->descriptor->getCSRFToken());
        }
        return $form;
    }

    public function end()
    {
        return '</form>';
    }

    public function errors()
    {
        $this->errors_rendered = true;
        return $this->error_renderer->renderList($this->descriptor);
    }

    public function label($field, $options = array())
    {
        return $this->descriptor->getField($field)->renderLabel($options);
    }

    public function render($field, array $options = array())
    {
        $element = $this->descriptor->getField($field);
        if ($this->descriptor->hasOption('name')) {

            $form_name = $this->descriptor->getOption('name');

            if (($pos = strpos($element->name, '[')) !== false) {
                $name = substr($element->name, 0, $pos);
                $extra = substr($element->name, $pos);
                $element->name = $form_name . '[' . $name . ']' . $extra;
            } else {
                $element->name = $form_name . '[' . $element->name . ']';
            }
        }
        if ($this->descriptor->hasErrors() && !$this->errors_rendered) {
            $form_errors = $this->descriptor->getErrors();
            if ($form_errors->fieldHasErrors($field)) {
                $errors = $form_errors->getFieldErrors($field);
                return $this->error_renderer->render($element, $options, $errors);
            }
        }
        return $element->render($options);
    }

}