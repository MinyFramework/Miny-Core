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

class Email extends Constraint
{
    public $message = 'The e-mail address "{address}" is not valid.';
    public $check_mx = false;

    public function validate($data)
    {
        if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
            if (!$this->check_mx) {
                return true;
            }
            $domain = substr($data, strpos('@', $data) + 1);
            if (checkdnsrr($domain . '.', 'MX')) {
                return true;
            }
        }

        $this->addViolation($this->message, array('address' => $data));
        return false;
    }

}