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
 * @package   Miny/Validator/Constraints
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0-dev
 */

namespace Miny\Validator\Constraints;

use ArrayAccess;
use InvalidArgumentException;
use Miny\Validator\Constraint;
use Traversable;
use UnexpectedValueException;

class Collection extends Constraint
{
    public $fields;
    public $allow_extra_fields = false;
    public $allow_missing_fields = false;
    public $extra_fields_message = 'The fields {fields} have not been expected.';
    public $missing_fields_message = 'The fields {fields} are missing.';

    public function __construct(array $params)
    {
        $properties = array(
            'fields', 'allow_extra_fields', 'allow_missing_fields',
            'extra_fields_message', 'missing_fields_message'
        );
        if (empty(array_intersect(array_keys($params), $properties))) {
            $params = array('fields' => $params);
        }

        parent::__construct($params);
    }

    public function validate($data)
    {
        if (!is_array($data) || !($data instanceof ArrayAccess && $data instanceof Traversable)) {
            throw new InvalidArgumentException('Data should be an array.');
        }

        $is_valid = true;

        if (!$this->allow_extra_fields) {
            $extra = array_keys(array_diff_key($data, $this->fields));
            if (!empty($extra)) {
                $parameters = array('fields'  => implode(', ', $extra));
                $this->addViolation($this->extra_fields_message, $parameters);
                $is_valid = false;
            }
        }

        if (!$this->allow_missing_fields) {
            $missing = array_keys(array_diff_key($this->fields, $data));
            if (!empty($missing)) {
                $parameters = array('fields'  => implode(', ', $missing));
                $this->addViolation($this->missing_fields_message, $parameters);
                $is_valid = false;
            }
        }

        foreach ($data as $key => $value) {
            if (!isset($this->fields[$key])) {
                continue;
            }

            $constraint = $this->fields[$key];

            if (!$constraint instanceof Constraint) {
                throw new UnexpectedValueException('Expected a constraint.');
            }

            if (!$constraint->validate($value)) {
                $this->addViolationList($constraint->getViolationList());
                $is_valid = false;
            }
        }
        return $is_valid;
    }

    public function getRequiredOptions()
    {
        return array('fields');
    }

}