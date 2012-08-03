<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;

class Type extends Constraint
{
    public $type;
    public $message = 'This value should be of type {type}';

    public function validate($data)
    {
        $type = strtolower($this->type);
        if ($type == 'boolean') {
            $type = 'bool';
        }

        if (function_exists('is_' . $type)) {
            if (call_user_func('is_' . $type, $data)) {
                return true;
            }
        } elseif (function_exists('ctype_' . $type)) {
            if (call_user_func('ctype_' . $type, $data)) {
                return true;
            }
        } else {
            if ($data instanceof $this->type) {
                return true;
            }
        }
        $this->addViolation($this->message, array('type' => $this->type));
        return false;
    }

    public function getRequiredOptions()
    {
        return array('type');
    }

    public function getDefaultOption()
    {
        return 'type';
    }

}