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
 * @package   Miny/Validator
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Validator;

class ConstraintViolation
{
    private $message_template;
    private $message_parameters;
    private $message;

    public function __construct($message_template, array $message_parameters = array())
    {
        $this->message_template = $message_template;
        $this->message_parameters = $message_parameters;
    }

    public function addParameter($key, $value)
    {
        return $this->message_parameters[$key] = $value;
    }

    public function getTemplate()
    {
        return $this->message_template;
    }

    public function getParameters()
    {
        return $this->message_parameters;
    }

    public function getMessage()
    {
        if (is_null($this->message)) {
            $keys = array();
            foreach (array_keys($this->message_parameters) as $key) {
                $keys[] = '{' . $key . '}';
            }
            $this->message = str_replace($keys, $this->message_parameters, $this->message_template);
        }
        return $this->message;
    }

    public function __toString()
    {
        return $this->getMessage();
    }

}