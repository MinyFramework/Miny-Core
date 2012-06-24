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
 * @package   Miny/Database
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\Database;

class Model
{
    private static $connections = array();

    public static function setConnection(\PDO $db, $name = 'default')
    {
        self::$connections[$name] = $db;
    }

    public static function getDBConnection($connection = 'default')
    {
        if (!isset(self::$connections[$connection])) {
            $message = 'Connection not set: ' . $connection;
            throw new \InvalidArgumentException($message);
        }
        return self::$connections[$connection];
    }

    private $connection_name;
    private $connection;

    public function __construct($connection = 'default')
    {
        $this->connection_name = $connection;
        $this->connection = self::getDBConnection($connection);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getConnectionName()
    {
        return $this->connection_name;
    }

}