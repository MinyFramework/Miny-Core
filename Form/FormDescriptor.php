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

use \Miny\Entity\Entity;
use \Miny\Validator\iValidable;
use \Miny\Validator\Descriptor;

class FormDescriptor extends Entity implements iValidable
{
    protected $fields = array();
    protected $options = array(
        'csrf'   => true,
        'method' => 'POST'
    );
    protected $token;
    private $token_storage;
    private $errors;

    public function __construct(array $data = array())
    {
        if ($this->hasOption('name') && !empty($data)) {
            $data = $data[$this->getOption('name')];
        }
        $this->fields = $this->fields();
        parent::__construct($data);
    }

    public function setTokenStorage(TokenStorage $storage)
    {
        $this->token_storage = $storage;
    }

    public function getTokenStorage()
    {
        return $this->token_storage;
    }

    public function getValidationInfo(Descriptor $class)
    {

    }

    protected function privates()
    {
        $array = parent::privates();
        array_push($array, 'options', 'felds', 'errors', 'token_storage');
        return $array;
    }

    public function fields()
    {
        return array();
    }

    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            throw new \OutOfBoundsException('Option not set: ' . $key);
        }
        return $this->options[$key];
    }

    public function hasOption($key)
    {
        return isset($this->options[$key]);
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function addField(FormElement $field)
    {
        $this->fields[$field->name] = $field;
    }

    public function getField($key)
    {
        if (!isset($this->fields[$key])) {
            throw new \OutOfBoundsException('Field not set: ' . $key);
        }
        return $this->fields[$key];
    }

    public function hasField($key)
    {
        return isset($this->fields[$key]);
    }

    public function __wakeup()
    {
        $this->token = $this->token_storage->generate();
    }

    public function getCSRFToken()
    {
        if (!$this->getOption('csrf')) {
            return NULL;
        }

        if (is_null($this->token)) {
            $this->token = $this->token_storage->generate();
        }
        return $this->token;
    }

    public function addErrors(array $errors)
    {
        if (is_null($this->errors)) {
            $this->errors = new FormErrorList;
        }
        $this->errors->addList($errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return !is_null($this->errors);
    }

}