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

    public function addRule($controller, $action, $permission) {
        if($action == '*' || is_null($action)) {
            $this->protected[$controller] = $permission;
        } else {
            if(!is_string($action)) {
                $message = 'Action must be string, %s given';
                $message = sprintf($message, gettype($action));
                throw new \InvalidArgumentException($message);
            }
            $this->protected[$controller][$action] = $permission;
        }
    }

    public function isActionProtected($controller, $action)
    {
        if (isset($this->protected[$controller])) {
            if (is_array($this->protected[$controller])) {
                if (isset($this->protected[$controller][$action])) {
                    return true;
                }
            } elseif (is_string($this->protected[$controller])) {
                //the whole controller is protected
                return true;
            }
        }
        return false;
    }

    public function getPermission($controller, $action)
    {
        if (isset($this->protected[$controller])) {
            if (is_array($this->protected[$controller])) {
                if (isset($this->protected[$controller][$action])) {
                    return $this->protected[$controller][$action];
                }
            } elseif (is_string($this->protected[$controller])) {
                //the whole controller is protected
                return $this->protected[$controller];
            }
        }
    }

}