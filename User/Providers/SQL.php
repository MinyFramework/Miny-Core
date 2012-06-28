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
 * @package   Miny/User/Providers
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\User\Providers;

use \Miny\User\AnonymUserIdentity;
use \Miny\User\UserIdentity;
use \Miny\User\UserProvider;

class SQL extends UserProvider
{
    private $driver;
    private $table_name;
    private $permissions_table_name;

    public function __construct(\PDO $driver, $users_table, $permissions_table)
    {
        $this->driver = $driver;
        $this->table_name = $users_table;
        $this->permissions_table_name = $permissions_table;
    }

    public function userExists($username)
    {
        if (parent::userExists($username)) {
            return true;
        }
        return $this->getUser($username) !== false;
    }

    private function getPermissions($username)
    {
        if (is_null($this->permissions_table_name)) {
            return array();
        }

        $sql = 'SELECT `permission` FROM `%s` WHERE `name` = ?';
        $sql = sprintf($sql, $this->permissions_table_name);

        $stmt = $this->driver->prepare($sql);
        $stmt->bindValue(1, $username);
        $stmt->execute();
        $array = array();
        foreach($stmt->fetchAll() as $permission) {
            $array[] = $permission['permission'];
        }
        return $array;
    }

    public function getUser($username)
    {
        $user = parent::getUser($username);
        if (!$user) {
            $sql = 'SELECT * FROM `%s` WHERE `name` = ?';
            $sql = sprintf($sql, $this->table_name);
            $stmt = $this->driver->prepare($sql);
            $stmt->bindValue(1, $username);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                return false;
            }
            $userdata = $stmt->fetch();
            $permissions = $this->getPermissions($userdata['name']);
            $user = new UserIdentity($userdata, $permissions);
            parent::addUser($user);
        }
        return $user;
    }

}