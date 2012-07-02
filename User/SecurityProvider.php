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
 * @package   Miny/User
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\User;

class SecurityProvider
{
    private $protected = array();

    public function __construct(array $rules = array())
    {
        foreach ($rules as $rule) {
            $this->addRule($rule['controller'], $rule['action'], $rule['rule']);
        }
    }

    public function addRule($controller, $action, $rule)
    {
        if (!is_string($action) && !is_callable($action)) {
            $message = 'Action must be string or callable type, %s given';
            $message = sprintf($message, gettype($action));
            throw new \InvalidArgumentException($message);
        }
        if (!$this->isRule($rule)) {
            $message = 'Invalid rule supplied.' .
                    'Rules must be string or callable types.';
            throw new \InvalidArgumentException($message);
        }
        if ($action == '*' || is_null($action)) {
            $this->protected[$controller]['*'] = $rule;
        } else {
            $this->protected[$controller][$action] = $rule;
        }
    }

    private function isRule($rule)
    {
        if (is_string($rule) || is_callable($rule)) {
            return true;
        }
        if (is_array($rule)) {
            foreach ($rule as $r) {
                if (!$this->isRule($r)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function isActionProtected($controller, $action)
    {
        if (!isset($this->protected[$controller])) {
            return false;
        }
        if (isset($this->protected[$controller]['*'])) {
            //the whole controller is protected
            return true;
        }
        if (isset($this->protected[$controller][$action])) {
            //the specific action is protected
            return true;
        }
        return false;
    }

    public function getPermission($controller, $action)
    {
        if (!isset($this->protected[$controller])) {
            return;
        }
        $return = array();
        if (isset($this->protected[$controller][$action])) {
            //the specific action is protected
            $permission = $this->protected[$controller][$action];

            if (is_callable($permission) || is_string($permission)) {
                $return[] = $permission;
            } else {
                $return = array_merge($return, $permission);
            }
        }
        if (isset($this->protected[$controller]['*'])) {
            //the whole controller is protected
            $permission = $this->protected[$controller]['*'];

            if (is_callable($permission) || is_string($permission)) {
                $return[] = $permission;
            } else {
                $return = array_merge($return, $permission);
            }
        }
        return $return;
    }

}