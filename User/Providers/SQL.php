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
    protected static $queries = array(
        'get_all'     => 'SELECT * FROM `%s`',
        'get_user'    => 'SELECT * FROM `%s` WHERE `name` = ?',
        'count_users' => 'SELECT COUNT(*) as `count` FROM `%s`'
    );

    public static function getQuery($query)
    {
        if (!isset(static::$queries[$query])) {
            throw new \OutOfBoundsException('Query not set: ' . $query);
        }
        return static::$queries[$query];
    }

    private $driver;
    private $table_name;
    private $loaded_all = false;
    private $user_count = 0;
    private $deleted_users = array();
    private $modified_users = array();
    private $new_users = array();

    public function __construct(\PDO $driver, $table_name)
    {
        $this->driver = $driver;
        $this->table_name = $table_name;
        $this->getUserCount();
        register_shutdown_function(array($this, 'saveModified'));
    }

    public function addUser(UserIdentity $user)
    {
        parent::addUser($user);
        if (isset($this->deleted_users[$user->name])) {
            unset($this->deleted_users[$user->name]);
            $this->modified_users[$user->name] = $user;
        } else {
            $this->new_users[$user->name] = $user;
            ++$this->user_count;
        }
    }

    public function removeUser($username)
    {
        $ret = parent::removeUser($username);
        if ($ret) {
            if (isset($this->new_users[$username])) {
                unset($this->new_users[$username]);
            }
            if (isset($this->modified_users[$username])) {
                unset($this->modified_users[$username]);
            }
            if (!isset($this->deleted_users[$username])) {
                $this->deleted_users[$username] = 1;
                --$this->user_count;
            }
        }
        return $ret;
    }

    public function getAnonymUser()
    {
        return new AnonymUserIdentity();
    }

    public function userExists($username)
    {
        if (parent::userExists($username)) {
            return true;
        }
        return $this->getUser($username) !== false;
    }

    public function countUsers()
    {
        if ($this->user_count == 0) {
            $sql = sprintf(self::getQuery('count_users'), $this->table_name);
            $stmt = $this->driver->prepare($sql);
            $stmt->execute();
            $c = $stmt->fetch();
            $this->user_count = $c['count'];
        }
        return $this->user_count;
    }

    public function getUser($username)
    {
        $user = parent::getUser($username);
        if (!$user) {
            $sql = sprintf(self::getQuery('get_user'), $this->table_name);
            $stmt = $this->driver->prepare($sql);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                return false;
            }

            $permissions = array(); //TODO: fetch the permissions
            $user = new UserIdentity($stmt->fetch(), $permissions);
            parent::addUser($user);
        }
        return $user;
    }

    public function getUsers()
    {
        if (!$this->loaded_all) {
            $sql = sprintf(self::getQuery('get_all'), $this->table_name);
            $stmt = $this->driver->prepare($sql);
            $stmt->execute();
            $count = $stmt->rowCount();
            if ($count > 0) {
                foreach ($stmt->fetchAll() as $userdata) {
                    if (!parent::userExists($userdata['name'])) {
                        $permissions = array(); //TODO: fetch the permissions
                        $user = new UserIdentity($userdata, $permissions);
                        parent::addUser($user);
                    }
                }
            }
            $this->loaded_all = true;
        }
        return parent::getUsers();
    }

    public function saveModified()
    {

    }

}