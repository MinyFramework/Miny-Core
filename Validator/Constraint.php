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

use Miny\Validator\Exceptions\ConstraintException;
use Miny\Validator\ConstraintViolation;

abstract class Constraint
{
    const DEFAULT_SCENARIO = 'default';

    public $scenario = array(self::DEFAULT_SCENARIO);
    public $message = 'This value is not valid.';
    private $violations;

    public function __construct($params)
    {
        $missing = array_flip((array) $this->getRequiredOptions());

        if (!is_array($params) || !is_string(key($params))) {
            $default = $this->getDefaultOption();
            if (is_null($default)) {
                $message = 'Default option is not set for this constraint.';
                throw new ConstraintException($message);
            }
            $params = array($default => $params);
        }

        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
                unset($missing[$key]);
            }
        }
        if (!empty($missing)) {
            $message = sprintf('Options "%s" must be set for this constraint.',
                    implode('", "', $missing));
            throw new ConstraintException($message);
        }
    }

    public function getRequiredOptions()
    {
        return array();
    }

    public function getDefaultOption()
    {
        return 'message';
    }

    public function constraintApplies($scenario = NULL)
    {
        $scenario = $scenario ? : self::DEFAULT_SCENARIO;
        return in_array($scenario, $this->scenario);
    }

    public function addViolation($message, array $parameters = array())
    {
        if (is_null($this->violations)) {
            $this->violations = new ConstraintViolationList;
        }
        $this->violations->addViolation($message, $parameters);
    }

    public function addViolationList(ConstraintViolationList $violations)
    {
        if (is_null($this->violations)) {
            $this->violations = $violations;
        } else {
            $this->violations->addViolationList($violations);
        }
    }

    public function getViolationList()
    {
        return $this->violations ? : new ConstraintViolationList;
    }

}