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

use Miny\Validator\Constraint;

class URL extends Constraint
{
    public $message = 'The URL "{address}" is not valid.';
    public $protocols = array('http', 'https');

    public function validate($data)
    {
        $protocol_pattern = implode('|', (array) $this->protocols);
        if (preg_match('/^(' . $protocol_pattern . ')/', $data)) {
            if (filter_var($data, FILTER_VALIDATE_URL)) {
                return true;
            }
        }

        $this->addViolation($this->message, array('address' => $data));
        return false;
    }

}