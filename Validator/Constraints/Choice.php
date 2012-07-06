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
 * @version   1.0
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;
use Miny\Validator\Exceptions\ConstraintException;

class Choice implements Constraint
{
    public $choices;
    public $callback;
    public $min;
    public $max;
    public $multiple = false;
    public $strict = false;
    public $message = 'The value should only be one of the following elements: {elements}';
    public $multiple_invalid_message = 'The value should be an array.';
    public $multiple_message = 'The array should only contain the following elements: {elements}';
    public $min_message = 'The array should contain at least {limit} elements.';
    public $max_message = 'The array should contain at most {limit} elements.';

    public function validate($data)
    {
        $valid = true;

        if (is_null($this->choices)) {

            $callback = $this->callback;
            if (!is_callable($callback) && !$callback instanceof \Closure) {
                $exception = 'There are no choices set to choose from.';
                throw new ConstraintException($exception);
            }
            $this->choices = call_user_func($callback);
        }

        if (!is_array($this->choices)) {
            $exception = 'Invalid choices set.';
            throw new ConstraintException($exception);
        }

        if ($this->multiple) {
            if (!is_array($data)) {
                $this->addViolation($this->multiple_invalid_message);
                $valid = false;
            }

            $elements = implode(', ', $this->choices);
            foreach ($data as $element) {
                if (!in_array($element, $this->choices, $this->strict)) {
                    $array = array(
                        'value'    => $element,
                        'elements' => $elements
                    );
                    $this->addViolation($this->multiple_message, $array);
                    $valid = false;
                }
            }

            $count = count($data);

            if (!is_null($this->min) && $count < $this->min) {
                $this->addViolation($this->min_message,
                        array(
                    'limit' => $this->min,
                    'count' => $count
                ));
                $valid = false;
            }
            if (!is_null($this->max) && $count > $this->max) {
                $this->addViolation($this->max_message,
                        array(
                    'limit' => $this->max,
                    'count' => $count
                ));
                $valid = false;
            }
        } elseif (!in_array($data, $this->choices, $this->strict)) {
            $elements = implode(', ', $this->choices);
            $array = array('value'    => $data, 'elements' => $elements);
            $this->addViolation($this->message, $array);
            $valid = false;
        }

        return $valid;
    }

    public function getDefaultOption()
    {
        return 'choices';
    }

}