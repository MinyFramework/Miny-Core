<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator;

class ConstraintViolation
{
    private $message_template;
    private $message_parameters;
    private $message;

    public function __construct($message_template, array $message_parameters = array())
    {
        $this->message_template = $message_template;
        $this->message_parameters = $message_parameters;
    }

    public function addParameter($key, $value)
    {
        return $this->message_parameters[$key] = $value;
    }

    public function getTemplate()
    {
        return $this->message_template;
    }

    public function getParameters()
    {
        return $this->message_parameters;
    }

    public function getMessage()
    {
        if (is_null($this->message)) {
            $keys = array();
            foreach (array_keys($this->message_parameters) as $key) {
                $keys[] = '{' . $key . '}';
            }
            $this->message = str_replace($keys, $this->message_parameters, $this->message_template);
        }
        return $this->message;
    }

    public function __toString()
    {
        return $this->getMessage();
    }

}