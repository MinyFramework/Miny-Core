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

/**
 * UserIdentity is a basic class for describing a user who has some
 * userdata as a key => data array and can have permissions.
 */
class UserIdentity
{
    private $changed;
    private $password;
    private $userdata = array();
    private $permissions = array();

    /**
     * Creates a UserIdentity
     *
     * @param array $userdata The userdata like username, e-mail, etc.
     * @param array $permissions Permissions the user has.
     */
    public function __construct(array $userdata, array $permissions = array())
    {
        if (isset($userdata['password'])) {
            $this->password = $userdata['password'];
            unset($userdata['password']);
        }
        $this->changed = false;
        $this->userdata = $userdata;
        $this->permissions = $permissions;
    }

    /**
     * Magic function to access userdata.
     * @param string $key
     * @return mixed The accessed userdata value
     * @throws \OutOfBoundsException if userdata is not set.
     */
    public function __get($key)
    {
        if (!isset($this->userdata[$key])) {
            throw new \OutOfBoundsException('Userdata not set: ' . $key);
        }
        return $this->userdata[$key];
    }

    /**
     * Magic function to set userdata.
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->userdata[$key] = $value;
        $this->changed = true;
    }

    /**
     * Returns the userdata
     * @return array
     */
    public function getData()
    {
        return $this->userdata;
    }

    /**
     * Checks whether the user has the given permission or not.
     * @param string $permission
     * @return boolean
     */
    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions);
    }

    /**
     * Returns the permissions the user has.
     * @return array The users' permissions
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Checks whether the user can be authenticated with
     * the given password.
     *
     * @param string $hash
     * @return boolean
     */
    public function checkPassword($password)
    {
        return $password === $this->password;
    }

    /**
     * Returns whether the user is anonym or not.
     * @return boolean
     */
    public function isAnonym()
    {
        return false;
    }

    /**
     * Returns whether the user has been changed.
     * @return boolean
     */
    public function isChanged()
    {
        return $this->changed;
    }

}