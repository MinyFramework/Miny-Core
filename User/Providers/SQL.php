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
    );

    public static function getQuery($query)
    {
        if (!isset(static::$queries[$query])) {
            throw new \OutOfBoundsException('Query not set: ' . $query);
        }
        return static::$queries[$query];
    }

    private $driver;
    private $deleted_users = array();
    private $modified_users = array();
    private $new_users = array();

    public function __construct(\PDO $driver)
    {
        $this->driver = $driver;
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
        }
    }

    public function removeUser($username)
    {
        parent::removeUser($username);
        if (isset($this->new_users[$username])) {
            unset($this->new_users[$username]);
        }
        if (isset($this->modified_users[$username])) {
            unset($this->modified_users[$username]);
        }
        if (!isset($this->deleted_users[$username])) {
            $this->deleted_users[$username] = 1;
        }
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
        //else we look it up in the DB
    }

    public function getUser($username)
    {
        $ret = parent::getUser($username);
        if (!$ret) {
            //else we look it up in the DB
        }
        return $ret;
    }

    public function getUsers()
    {
        //we look them up in the DB and also cache them
    }

    public function saveModified()
    {

    }

}