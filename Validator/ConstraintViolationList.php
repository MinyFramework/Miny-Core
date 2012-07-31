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
 * @version   1.0-dev
 */

namespace Miny\Validator;

use ArrayIterator;
use IteratorAggregate;

class ConstraintViolationList implements IteratorAggregate
{
    private $violations = array();

    public function addViolation($message, array $parameters = array())
    {
        $this->violations[] = new ConstraintViolation($message, $parameters);
    }

    public function addViolationList(ConstraintViolationList $other)
    {
        $this->violations = array_merge($other->violations, $this->violations);
    }

    public function getViolationList()
    {
        return $this->violations;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->violations);
    }

}