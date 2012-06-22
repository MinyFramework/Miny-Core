<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Translation
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Translation;

class Translation {

    private $strings = array();
    private $rules = array();

    public function __construct(array $rules) {
        foreach ($rules as $name => $rule) {
            $rule = preg_replace('/[^n0-9\w=\-+%<>]/', '', $rule);
            $this->rules[$name] = $rule;
        }
    }

    public function addString($key, $string) {
        if(is_array($string)) {
            if(count($string) == 1) {
                $string = current($string);
            }
        }
        $this->strings[$key] = $string;
    }

    private function getStringForN(array $string, $num) {
        $fallback = NULL;
        foreach ($string as $q => $str) {
            if (is_int($q)) {
                if ($num === $q) {
                    return $str;
                }
            } elseif ($q == 'other') {
                $fallback = $str;
            } elseif (isset($this->rules[$q]) && $this->ruleApplies($this->rules[$q], $num)) {
                return $str;
            }
        }
        return $fallback;
    }

    private function ruleApplies($rule, $num) {
        if (!is_int($num)) {
            return false;
        }
        $rule = str_replace('n', $num, $rule);
        return eval('return (' . $rule . ');');
    }

    public function get($key, $num = NULL) {
        if (isset($this->strings[$key])) {
            $string = $this->strings[$key];
        } else {
            $string = $key;
        }
        if (is_array($string)) {
            $str = $this->getStringForN($string, $num);
            if (is_null($str)) {
                $string = $key;
            } else {
                $string = $str;
            }
        }

        $arg_num = func_num_args();
        if ($arg_num > 1) {
            $replace_arr = array(
                '{n}' => $num
            );
            for ($i = 2; $i < $arg_num; ++$i) {
                $replace_arr['{' . $i - 2 . '}'] = func_get_arg($i);
            }
            $string = str_replace(array_keys($replace_arr), $replace_arr, $string);
        }
        return $string;
    }

}