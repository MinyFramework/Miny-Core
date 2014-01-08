<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Utils;

/**
 * String implements a set of utility methods regarding easier or more secure string handling.
 *
 * @author Dániel Buga
 */
class StringUtils
{

    /**
     * Compares two strings securely.
     * @link http://blog.astrumfutura.com/2010/10/nanosecond-scale-remote-timing-attacks-on-php-applications-time-to-take-them-seriously/ Implementation source
     *
     * @param string $known
     * @param string $user
     * @return boolean
     */
    public static function compare($known, $user)
    {
        if (strlen($known) !== strlen($user)) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < strlen($known); $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }
        return $result == 0;
    }
}
