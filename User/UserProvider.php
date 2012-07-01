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

class UserProvider
{
    private $user_key = 'name';
    private $users = array();

    public function __construct($key_field = NULL)
    {
        if (!is_null($key_field)) {
            $this->user_key = $key_field;
        }
    }

    public function getKeyName()
    {
        return $this->user_key;
    }

    public function addUser(UserIdentity $user)
    {
        $key = $user->get($this->getKeyName());
        $this->users[$key] = $user;
        return true;
    }

    public function removeUser($key)
    {
        if (isset($this->users[$key])) {
            unset($this->users[$key]);
            return true;
        }
    }

    public function getAnonymUser()
    {
        return new AnonymUserIdentity();
    }

    public function userExists($key)
    {
        return isset($this->users[$key]);
    }

    public function getUser($key)
    {
        if (!$this->userExists($key)) {
            return false;
        }
        return $this->users[$key];
    }

}