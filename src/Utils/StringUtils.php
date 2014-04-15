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
     *
     * @link http://blog.astrumfutura.com/2010/10/nanosecond-scale-remote-timing-attacks-on-php-applications-time-to-take-them-seriously/ Implementation source
     *
     * @param string $known
     * @param string $user
     *
     * @return boolean
     */
    public static function compare($known, $user)
    {
        if (strlen($known) !== strlen($user)) {
            return false;
        }
        $result      = 0;
        $knownLength = strlen($known);
        for ($i = 0; $i < $knownLength; $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }

        return $result == 0;
    }

    /**
     * @param string $str The string that will be converted to camelCase
     *
     * @return string
     */
    public static function camelize($str)
    {
        $str = strtolower($str);
        $str = strtr($str, '_', ' ');
        $str = preg_replace('/\s+/', '', ucwords($str));

        return lcfirst($str);
    }

    /**
     * @param string $str The camelCase string that will be split to words
     * @param string $separator
     *
     * @return string
     */
    public static function decamelize($str, $separator = ' ')
    {
        $str = preg_replace('/(?<=[a-z])([A-Z])/', $separator . '$1', $str);

        return strtolower($str);
    }
}
