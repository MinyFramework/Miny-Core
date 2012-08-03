<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator;

use Miny\Validator\Exceptions\ConstraintException;

abstract class Constraint
{
    const DEFAULT_SCENARIO = 'default';

    public $scenario = array(self::DEFAULT_SCENARIO);
    public $message = 'This value is not valid.';
    private $violations;

    public function __construct($params = NULL)
    {
        $missing = array_flip((array) $this->getRequiredOptions());

        if (empty($missing) && empty($params)) {
            return;
        }

        if (!is_array($params) || !is_string(key($params))) {
            $default = $this->getDefaultOption();
            if (is_null($default)) {
                throw new ConstraintException('Default option is not set for this constraint.');
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
            $message = sprintf('Options "%s" must be set for this constraint.', implode('", "', $missing));
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