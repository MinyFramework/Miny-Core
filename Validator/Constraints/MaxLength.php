<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;

class MaxLength extends Constraint
{
    public $limit;
    public $message = 'The string should be at most {limit} characters long.';
    public $invalid_message = 'The data is not a string.';

    public function validate($data)
    {
        if (!is_string($data) && !method_exists($data, '__toString')) {

            $this->addViolation($this->invalid_message,
                    array('limit' => $this->limit));
        } else {
            if (strlen((string) $data) <= $this->limit) {
                return true;
            }

            $this->addViolation($this->message, array('limit' => $this->limit));
        }
        return false;
    }

    public function getRequiredOptions()
    {
        return array('limit');
    }

    public function getDefaultOption()
    {
        return 'limit';
    }

}