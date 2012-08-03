<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator;

use Miny\Validator\Descriptor;

interface iValidable
{
    public function getValidationInfo(Descriptor $class);
}