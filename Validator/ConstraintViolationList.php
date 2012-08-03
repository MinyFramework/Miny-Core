<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
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