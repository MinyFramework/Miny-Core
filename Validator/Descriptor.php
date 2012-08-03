<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator;

class Descriptor
{
    private $constraints = array();

    public function getConstraints($type)
    {
        if (!isset($this->constraints[$type])) {
            return array();
        }
        return $this->constraints[$type];
    }

    public function addClassConstraint($name, Constraint $constraint)
    {
        $this->addConstraint('class', $name, $constraint);
    }

    public function addGetterConstraint($name, Constraint $constraint)
    {
        $this->addConstraint('getter', $name, $constraint);
    }

    public function addPropertyConstraint($name, Constraint $constraint)
    {
        $this->addConstraint('property', $name, $constraint);
    }

    private function addConstraint($type, $name, Constraint $constraint)
    {
        if (!isset($this->constraints[$type])) {
            $this->constraints[$type] = array();
        }
        if (!isset($this->constraints[$type][$name])) {
            $this->constraints[$type][$name] = array();
        }
        $this->constraints[$type][$name][] = $constraint;
    }

}