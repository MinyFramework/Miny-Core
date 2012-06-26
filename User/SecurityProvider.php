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

    public function addRule($controller, $action, $permission)
    {
        if (!is_string($action) && !is_callable($action)) {
            $message = 'Action must be string or callable type, %s given';
            $message = sprintf($message, gettype($action));
            throw new \InvalidArgumentException($message);
        }
        if ($action == '*' || is_null($action)) {
            $this->protected[$controller] = $permission;
        } else {
            $this->protected[$controller][$action] = $permission;
        }
    }

    private function isRule($rule)
    {
        return is_string($rule) || is_callable($rule);
    }

    public function isActionProtected($controller, $action)
    {
        if (!isset($this->protected[$controller])) {
            return false;
        }
        if ($this->isRule($this->protected[$controller])) {
            //the whole controller is protected
            return true;
        } elseif (is_array($this->protected[$controller])) {
            if (isset($this->protected[$controller][$action])) {
                //the specific action is protected
                return true;
            }
        }
        return false;
    }

    public function getPermission($controller, $action)
    {
        if (!isset($this->protected[$controller])) {
            return;
        }
        if ($this->isRule($this->protected[$controller])) {
            //the whole controller is protected
            return $this->protected[$controller];
        } elseif (is_array($this->protected[$controller])) {
            if (isset($this->protected[$controller][$action])) {
                //the specific action is protected
                return $this->protected[$controller][$action];
            }
        }
        //not protected - nothing to return
    }

}