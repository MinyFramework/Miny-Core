<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;

class Blank extends Constraint
{
    public $message = 'This value should be blank.';

    public function validate($data)
    {
        if (empty($data)) {
            return true;
        }

        $this->addViolation($this->message);
        return false;
    }

    public function getDefaultOption()
    {
        return 'message';
    }

}