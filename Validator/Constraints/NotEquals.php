<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;

class NotEquals extends Constraint
{
    public $data;
    public $message = 'The data match but they should not.';

    public function validate($data)
    {
        if ($data != $this->data) {
            return true;
        }

        $this->addViolation($this->message);
        return false;
    }

    public function getRequiredOptions()
    {
        return array('data');
    }

    public function getDefaultOption()
    {
        return 'data';
    }

}