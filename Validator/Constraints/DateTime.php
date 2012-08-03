<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Exception;
use Miny\Validator\Constraint;

class DateTime extends Constraint
{
    public $format;

    public function validate($value)
    {
        if ($value instanceof \DateTime) {
            return true;
        }

        if (is_null($this->format)) {
            try {
                new \DateTime($value);
                return true;
            } catch (Exception $e) {

            }
        } elseif (\DateTime::createFromFormat($this->format, $value)) {
            return true;
        }
        $this->addViolation($this->message, array('value' => $value));
        return false;
    }

    public function getDefaultOption()
    {
        return 'format';
    }

}