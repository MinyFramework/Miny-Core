<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;

class Valid extends Constraint
{
    public $message = 'This value should be valid.';

    public function getDefaultOption()
    {
        return 'message';
    }

}