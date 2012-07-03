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

use \Miny\Session\Session;
use \Miny\Template\Template;

class Widget extends \Miny\Widget\Widget
{
    public static $error_template = 'widgets/ui/form_error';
    public $data = array();
    public $errors = array();
    private $templating;
    private $session;
    private $params;

    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    public function setTemplating(Template $templating)
    {
        $this->templating = $templating;
    }

    public function __call($type, $args)
    {
        switch ($type) {
            case 'datetime_local':
                $type = 'datetime-local';
            case 'button':
            case 'color':
            case 'date':
            case 'datetime':
            case 'email':
            case 'file':
            case 'hidden':
            case 'image':
            case 'month':
            case 'number':
            case 'password':
            case 'range':
            case 'reset':
            case 'search':
            case 'submit':
            case 'tel':
            case 'text':
            case 'time':
            case 'url':
            case 'week':
                $args[0]['type'] = $type;
                $this->input($args[0]);
                break;
            default:
                $message = 'Unknown form element: ' . $type;
                throw new \InvalidArgumentException($message);
        }
    }

    public function getData($name, $default = NULL)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return $default;
    }

    public function getErrors($name)
    {
        if (isset($this->errors[$name])) {
            if (!is_array($this->errors[$name])) {
                $this->errors[$name] = array($this->errors[$name]);
            }
            return $this->errors[$name];
        }
        return array();
    }

    private function getHTMLArgList(array $args)
    {
        $arglist = '';
        foreach ($args as $name => $value) {
            $arglist .= sprintf(' %s="%s"', $name, $value);
        }
        return $arglist;
    }

    private function renderElement($element, array $errors = array())
    {
        if (!empty($errors) && !is_null($this->templating)) {
            $this->templating->setScope('form_error');

            $this->templating->element = $element;
            $this->templating->errors = $errors;

            echo $this->templating->render(self::$error_template);

            $this->templating->leaveScope(true);
        } else {
            echo $element;
        }
    }

    public function label(array $args)
    {
        if (isset($args['text'])) {
            $text = $args['text'];
            unset($args['text']);
        } else {
            $text = 'label';
        }
        $arglist = $this->getHTMLArgList($args);
        printf('<label%s>%s</label>', $arglist, $text);
    }

    public function textarea(array $args)
    {
        if (isset($args['name'])) {
            $text = $this->getData($args['name']);
            $errors = $this->getErrors($args['name']);
        } else {
            $text = '';
            $errors = array();
        }

        $arglist = $this->getHTMLArgList($args);
        $element = sprintf('<textarea%s>%s</textarea>', $arglist, $text);
        $this->renderElement($element, $errors);
    }

    public function input(array $args)
    {
        if (isset($args['name'])) {
            $errors = $this->getErrors($args['name']);
            $value = $this->getData($args['name']);
            if ($value) {
                $args['value'] = $value;
            }
        } else {
            $errors = array();
        }

        $arglist = $this->getHTMLArgList($args);
        $element = sprintf('<input%s/>', $arglist);
        $this->renderElement($element, $errors);
    }

    public function checkbox(array $args)
    {
        $args['type'] = 'checkbox';

        if (isset($args['name'])) {
            $value = $this->getData($args['name']);
            $errors = $this->getErrors($args['name']);
            if (!is_null($value)) {
                $args['checked'] = 'checked';
            }
        } else {
            $errors = array();
        }

        $arglist = $this->getHTMLArgList($args);
        $element = sprintf('<input%s/>', $arglist);
        $this->renderElement($element, $errors);
    }

    public function radio(array $args)
    {
        $args['type'] = 'radio';

        if (isset($args['name'])) {
            $value = $this->getData($args['name']);
            $errors = $this->getErrors($args['name']);
            if (isset($args['value']) && $args['value'] == $value) {
                $args['checked'] = 'checked';
            }
        } else {
            $errors = array();
        }

        $arglist = $this->getHTMLArgList($args);
        $element = sprintf('<input%s/>', $arglist);
        $this->renderElement($element, $errors);
    }

    public function select(array $args, array $options)
    {
        if (isset($args['name'])) {
            $values = $this->getData($args['name']);
            $errors = $this->getErrors($args['name']);

            if (isset($args['multiple'])) {
                $args['name'] .= '[]';
            } else {
                $values = array($values);
            }
        } else {
            $values = array();
            $errors = array();
        }

        $option = '<option value="%s">%s</option>';
        $option_selected = '<option value="%s" selected="selected">%s</option>';
        $optgroup = '<optgroup label="%s">%s</optgroup>';

        $rendered_options = array();
        foreach ($options as $name => $value) {
            if (is_array($value)) {
                $temp = array();
                foreach ($value as $key => $val) {
                    if (in_array($val, $values)) {
                        $temp[] = sprintf($option_selected, $val, $key);
                    } else {
                        $temp[] = sprintf($option, $val, $key);
                    }
                }
                $rendered_options[] = sprintf($optgroup, $name,
                        implode("\n", $temp));
            } elseif (in_array($value, $values)) {
                $rendered_options[] = sprintf($option_selected, $value, $name);
            } else {
                $rendered_options[] = sprintf($option, $value, $name);
            }
        }

        $arglist = $this->getHTMLArgList($args);
        $element = sprintf('<select%s>%s</select>', $arglist,
                implode("\n", $rendered_options));
        $this->renderElement($element, $errors);
    }

    public function begin(array $params = array(), array $data = array(),
                          array $errors = array())
    {
        ob_start();
        $this->params = $params;
        $this->data = $data;
        $this->errors = $errors;
        return $this;
    }

    public function run(array $params = array())
    {
        $params = $params + $this->params;

        if (!isset($params['method'])) {
            $params['method'] = 'GET';
        }
        if ($params['method'] != 'GET') {
            if ($params['method'] != 'POST') {
                printf('<input type="hidden" name="_method" value="%s" />',
                        $params['method']);
            }
            $params['method'] = 'POST';
        }
        if (!is_null($this->session)) {
            $token = md5(mt_rand());
            $tokens = $this->session->flash('tokens');
            $tokens[] = $token;
            $this->session->flash('tokens', $tokens);
            printf('<input type="hidden" name="token" value="%s" />', $token);
        }

        $content = ob_get_clean();
        $arglist = $this->getHTMLArgList($params);
        printf('<form%s>%s</form>', $arglist, $content);
        return false;
    }

}