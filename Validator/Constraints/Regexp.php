<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;

class Regexp extends Constraint
{
    public $pattern;
    public $match = true;

    public function validate($data)
    {
        if (preg_match($this->pattern, $data) == $this->match) {
            return true;
        }

        $this->addViolation($this->message);
        return false;
    }

    public function getRequiredOptions()
    {
        return array('pattern');
    }

    public function getDefaultOption()
    {
        return 'pattern';
    }

}