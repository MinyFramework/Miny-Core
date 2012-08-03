<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;

class URL extends Constraint
{
    public $message = 'The URL "{address}" is not valid.';
    public $protocols = array('http', 'https');

    public function validate($data)
    {
        $protocol_pattern = implode('|', (array) $this->protocols);
        if (preg_match('/^(' . $protocol_pattern . ')/', $data)) {
            if (filter_var($data, FILTER_VALIDATE_URL)) {
                return true;
            }
        }

        $this->addViolation($this->message, array('address' => $data));
        return false;
    }

}