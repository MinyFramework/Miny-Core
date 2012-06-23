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

use \Miny\Template\Template;

class Widget extends \Miny\Widget\Widget {

    public static $error_template = 'widgets/ui/form/error';
    public $data = array();
    public $errors = array();
    private $templating;

    public function setTemplating(Template $templating) {
        $this->templating = $templating;
    }

    public function __call($type, $args) {
        switch ($type) {
            case 'hidden':
            case 'text':
            case 'email':
            case 'submit':
            case 'reset':
                $this->input($type, $args);
                break;
            default:
                throw new \InvalidArgumentException('Unknown form element: ' . $type);
        }
    }

    public function getData($name, $default = NULL) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return $default;
    }

    public function getErrors($name) {
        if (isset($this->errors[$name])) {
            if (!is_array($this->errors[$name])) {
                $this->errors[$name] = array($this->errors[$name]);
            }
            return $this->errors[$name];
        }
        return array();
    }

    private function getHTMLArgList(array $args) {
        $arglist = '';
        foreach ($args as $name => $value) {
            $arglist .= sprintf(' %s="%s"', $name, $value);
        }
        return $arglist;
    }

    private function renderError($element, array $errors = array()) {
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

    public function textarea(array $args) {

        if (isset($args['name'])) {
            $text = $this->getData($args['name']);
            $errors = $this->getErrors($args['name']);
        } else {
            $text = '';
            $errors = array();
        }

        $arglist = $this->getHTMLArgList($args);
        $element = sprintf('<textarea%s>%s</textarea>', $arglist, $text);
        $this->renderError($element, $errors);
    }

    public function input($type, array $args) {

        if (isset($args[0]['name'])) {
            $value = $this->getData($args[0]['name']);
            if ($value) {
                $args[0]['value'] = $value;
            }
            $errors = $this->getErrors($args[0]['name']);
        } else {
            $errors = array();
        }

        $params = array('type'  => $type);
        $params = $params + $args[0];

        $arglist = $this->getHTMLArgList($params);
        $element = sprintf('<input%s/>', $arglist);
        $this->renderError($element, $errors);
    }

    public function label(array $args) {
        if (isset($args['text'])) {
            $text = $args['text'];
            unset($args['text']);
        } else {
            $text = 'label';
        }
        $arglist = $this->getHTMLArgList($args);
        printf('<label%s>%s</label>', $arglist, $text);
    }

    public function checkbox(array $args) {

    }

    public function radio(array $args) {

    }

    public function select(array $args) {

    }

    public function begin(array $params = array(), array $data = array(), array $errors = array()) {
        ob_start();
        $this->params = $params;
        $this->data = $data;
        $this->errors = $errors;
        return $this;
    }

    public function run(array $params = array()) {
        $params = $params + $this->params;

        if ($params['method'] != 'GET') {
            if ($params['method'] != 'POST') {
                printf('<input type="hidden" name="_method" value="%s" />', $params['method']);
            }
            $params['method'] = 'POST';
        }

        $content = ob_get_clean();
        $arglist = $this->getHTMLArgList($params);
        printf('<form%s>%s</form>', $arglist, $content);
        return false;
    }

}