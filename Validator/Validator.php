<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator;

use UnexpectedValueException;

class Validator
{
    protected function loadConstraints(iValidable $object)
    {
        $class = new Descriptor;
        $object->getValidationInfo($class);
        return $class;
    }

    public function validate(iValidable $object, $scenario = NULL)
    {
        $class = $this->loadConstraints($object);
        $valid = true;
        $violations = array();
        foreach ($class->getConstraints('class') as $array) {
            foreach ($array as $constraint) {
                $is_valid = $this->validateValue($object, $constraint, $scenario);
                if ($is_valid !== true) {
                    $list = $constraint->getViolationList();
                    if (!isset($violations['class'])) {
                        $violations['class'] = $list;
                    } else {
                        $violations['class']->addViolationList($list);
                    }
                    $valid = false;
                }
            }
        }

        foreach ($class->getConstraints('getter') as $getter => $array) {
            foreach ($array as $constraint) {
                $data = call_user_func(array($object, $getter));
                $is_valid = $this->validateValue($data, $constraint, $scenario);
                if ($is_valid !== true) {
                    $list = $constraint->getViolationList();
                    if (!isset($violations[$getter])) {
                        $violations[$getter] = $list;
                    } else {
                        $violations[$getter]->addViolationList($list);
                    }
                    $valid = false;
                }
            }
        }

        foreach ($class->getConstraints('property') as $property => $array) {
            foreach ($array as $constraint) {
                $data = $object->$property;
                $is_valid = $this->validateValue($data, $constraint, $scenario);
                if ($is_valid !== true) {
                    $list = $constraint->getViolationList();
                    if (!isset($violations[$property])) {
                        $violations[$property] = $list;
                    } else {
                        $violations[$property]->addViolationList($list);
                    }
                    $valid = false;
                }
            }
        }
        if ($valid) {
            return true;
        }

        return $violations;
    }

    public function validateValue($data, Constraint $constraint, $scenario = NULL)
    {
        if (!$constraint->constraintApplies($scenario)) {
            return true;
        }
        if (!method_exists($constraint, 'validate')) {
            if (!$data instanceof iValidable) {
                $message = 'Cannot validate variable.';
                throw new UnexpectedValueException($message);
            }
            if (!$this->validate($data, $scenario)) {
                $constraint->addViolation($constraint->message);
                return false;
            }
        } elseif (!$constraint->validate($data)) {
            return $constraint->getViolationList();
        }
        return true;
    }

}