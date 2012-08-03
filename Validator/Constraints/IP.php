<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Validator\Constraints;

use Miny\Validator\Constraint;
use Miny\Validator\Exceptions\ConstraintException;

class IP extends Constraint
{
    const V4 = 'v4';
    const V6 = 'v6';
    const V_ALL = 'all';
    const O_ALL = 'all_ranges';
    const O_NO_PRIV = 'no_private';
    const O_NO_RES = 'no_reserved';
    const O_ONLY_PUBLIC = 'only_public';

    private static $versions = array(
        IP::V4,
        IP::V6,
        IP::V_ALL
    );
    private static $options = array(
        IP::O_ALL,
        IP::O_NO_PRIV,
        IP::O_NO_RES,
        IP::O_ONLY_PUBLIC
    );
    public $message = 'The IP address "{address}" is not valid.';
    public $version = IP::V_ALL;
    public $options = IP::O_ALL;

    public function __construct(array $params)
    {
        parent::__construct($params);
        if (!in_array($this->version, self::$versions)) {
            throw new ConstraintException('Invalid version set.');
        }
        if (!in_array($this->options, self::$options)) {
            throw new ConstraintException('Invalid options set.');
        }
    }

    public function validate($data)
    {
        $flags = NULL;
        switch ($this->version) {
            case IP::V4:
                $flags = FILTER_FLAG_IPV4;
                break;
            case IP::V6:
                $flags = FILTER_FLAG_IPV6;
                break;
        }

        switch ($this->options) {
            case IP::O_NO_PRIV:
                $flags |= FILTER_FLAG_NO_PRIV_RANGE;
                break;
            case IP::O_NO_RES:
                $flags |= FILTER_FLAG_NO_RES_RANGE;
                break;
            case IP::O_ONLY_PUBLIC:
                $flags |= FILTER_FLAG_NO_PRIV_RANGE;
                $flags |= FILTER_FLAG_NO_RES_RANGE;
                break;
        }

        if (filter_var($data, FILTER_VALIDATE_IP, $flags)) {
            return true;
        }

        $this->addViolation($this->message, array('address' => $data));
        return false;
    }

    public function getDefaultOption()
    {
        return 'version';
    }

}