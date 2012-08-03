<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
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