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
 * AnonymUserIdentity is a basic class for describing an unauthenticated user.
 */
class AnonymUserIdentity extends UserIdentity
{
    /**
     * Creates an anonym UserIdentity
     *
     */
    public function __construct()
    {

    }

    /**
     * Magic function to access userdata.
     * @param string $key
     * @return mixed The accessed userdata value
     * @throws \OutOfBoundsException if userdata is not set.
     */
    public function __get($key)
    {
        return NULL;
    }

    /**
     * Checks whether the user has the given permission or not.
     * @param string $permission
     * @return boolean
     */
    public function hasPermission($permission)
    {
        return false;
    }

    /**
     * Returns whether the user is anonym or not.
     * @return boolean
     */
    public function isAnonym()
    {
        return true;
    }

    /**
     * Returns whether the given user is identical to the current one.
     * @return boolean
     */
    public function isIdentical(UserIdentity $other)
    {
        return false;
    }

}