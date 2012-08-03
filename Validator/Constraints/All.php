<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;
use UnexpectedValueException;

class All extends Constraint
{
    public $constraints = array();

    public function validate($data)
    {
        if (!is_array($this->constraints)) {
            $this->constraints = array($this->constraints);
        }
        $is_valid = true;
        foreach ($this->constraints as $constraint) {
            if (!$constraint instanceof Constraint) {
                throw new UnexpectedValueException('Invalid parameter set.');
            }
            if (!$constraint->validate($data)) {
                $this->addViolationList($constraint->getViolationList());
                $is_valid = false;
            }
        }
        return $is_valid;
    }

    public function getRequiredOptions()
    {
        return array('constraints');
    }

    public function getDefaultOption()
    {
        return 'constraints';
    }

}