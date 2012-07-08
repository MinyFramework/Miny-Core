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

class MaxLength extends Constraint
{
    public $limit;
    public $message = 'The string should be at most {limit} characters long.';
    public $invalid_message = 'The data is not a string.';

    public function validate($data)
    {
        if (!is_string($data) && !method_exists($data, '__toString')) {

            $this->addViolation($this->invalid_message,
                    array('limit' => $this->limit));
        } else {
            if (strlen((string) $data) <= $this->limit) {
                return true;
            }

            $this->addViolation($this->message, array('limit' => $this->limit));
        }
        return false;
    }

    public function getRequiredOptions()
    {
        return array('limit');
    }

    public function getDefaultOption()
    {
        return 'limit';
    }

}