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

use \Miny\Entity\Entity;
use \Miny\Entity\EntityProvider;

/**
 * UserIdentity is a basic class for describing a user.
 */
class UserIdentity extends Entity
{
    protected $password;
    protected $name;
    protected $email;
    protected $display_name;
    protected $permissions = array();

    /**
     * The primary key of the User entity.
     */
    public static function getKeyName()
    {
        return 'name';
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
     * Returns whether the user is anonym or not.
     * @return boolean
     */
    public function isAnonym()
    {
        return false;
    }

}