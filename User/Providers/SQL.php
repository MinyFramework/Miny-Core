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
    private $modified_users = array();

    public function __construct(\PDO $driver, $users_table, $permissions_table)
    {
        $this->driver = $driver;
        $this->table_name = $users_table;
        $this->permissions_table_name = $permissions_table;
        register_shutdown_function(array($this, 'save'));
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
        $stmt->execute(array($username));
        return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    public function getUser($username)
    {
        $user = parent::getUser($username);
        if (!$user) {
            $user = $this->getUserBySQL('WHERE `name` = ?', array($username));
            parent::addUser($user);
        }
        return $user;
    }

    private function createUser(array $userdata)
    {
        if (!$this->userExists($userdata['name'])) {
            $permissions = $this->getPermissions($userdata['name']);
            $user = new UserIdentity($userdata, $permissions);
            parent::addUser($user);
            return $user;
        } else {
            return parent::getUser($userdata['name']);
        }
    }

    public function getUserBySQL($sql, array $params = NULL)
    {
        $sql = 'SELECT * FROM `%s` ' . $sql;
        $stmt = $this->driver->prepare(sprintf($sql, $this->table_name));
        $stmt->execute($params);
        switch ($stmt->rowCount()) {
            case 0:
                return false;
            case 1:
                $userdata = $stmt->fetch();
                return $this->createUser($userdata);
            default:
                $return = array();
                foreach ($stmt->fetchAll() as $userdata) {
                    $return[] = $this->createUser($userdata);
                }
                return $return;
        }
    }

    public function addUser(UserIdentity $user)
    {
        $state = $this->userExists($user->name) ? 'm' : 'a';
        $this->modified_users[$user->name] = $state;
        return parent::addUser($user);
    }

    public function removeUser($username)
    {
        if (parent::removeUser($username)) {
            $this->modified_users[$username] = 'r';
            return true;
        }
    }

    public function save()
    {
        if (empty($this->modified_users)) {
            return;
        }
        $this->driver->beginTransaction();
        foreach ($this->modified_users as $username => $state) {
            switch ($state) {
                case 'a':
                case 'm':
                    $this->saveUser(parent::getUser($username));
                    break;
                case 'r':
                    $this->deleteUser($username);
                    break;
            }
        }
        $this->driver->commit();
    }

    private function deleteUser($username)
    {
        $pattern = 'DELETE FROM `%s` WHERE `%s` = ?';
        $tables = array(
            $this->table_name             => 'name',
            $this->permissions_table_name => 'name'
        );
        $uname = array($username);
        foreach ($tables as $table => $key) {
            $stmt = $this->driver->prepare(sprintf($pattern, $table, $key));
            $stmt->execute($uname);
        }
    }

    private function saveUser(UserIdentity $user)
    {
        //TODO: clean this stuff up, looks ugly
        //update userdata
        $fields = array();
        $data = $user->getData();
        $data_keys = array_keys($data);
        foreach ($data_keys as $name) {
            $fields[] = '`' . $name . '`';
        }
        $fields = implode(', ', $fields);
        $values = implode(', ', $data_keys);

        $sql = 'REPLACE INTO `%s` (%s) VALUES (%s)';
        $sql = sprintf($sql, $this->table_name, $fields, $values);
        $this->driver->prepare($sql)->execute($data);

        //delete removed permissions
        $permissions = $user->getPermissions();
        $permission_count = count($permissions);

        //no permissions - delete all old ones and return
        if ($permission_count == 0) {
            $sql = 'DELETE FROM `%s` WHERE `%s` = ?';
            $sql = sprintf($sql, $this->permissions_table_name, 'name');
            $this->driver->prepare($sql)->execute(array($user->name));
            return;
        }
        //delete only removed permissions
        $marks = array_fill(0, $permission_count, '?');
        $marks = implode(', ', $marks);
        $sql = 'DELETE FROM `%s` WHERE `%s` = ? AND `permission` NOT IN(%s)';
        $sql = sprintf($sql, $this->permissions_table_name, 'name', $marks);
        
        $array = $permissions;
        array_unshift($array, $user->name);
        $this->driver->prepare($sql)->execute($array);

        //insert new permissions
        $marks = array_fill(0, $permission_count, '(?, ?)');
        $marks = implode(', ', $marks);
        $sql = 'REPLACE INTO `%s` (`%s`, `permission`) VALUES %s';
        $sql = sprintf($sql, $this->permissions_table_name, 'name', $marks);

        $array = array();
        foreach ($permissions as $permission) {
            $array[] = $user->name;
            $array[] = $permission;
        }
        $this->driver->prepare($sql)->execute($array);
    }

}