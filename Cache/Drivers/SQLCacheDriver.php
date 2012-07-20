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
 * @package   Miny/Cache
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\Cache\Drivers;

class SQLCacheDriver implements \Miny\Cache\iCacheDriver
{
    protected static $queries = array(
        'gc'     => 'DELETE FROM `%s` WHERE `expiration` < NOW()',
        'index'  => 'SELECT `key` FROM `%s` WHERE `expiration` >= NOW()',
        'select' => 'SELECT `data` FROM `%s` WHERE `key` = ?',
        'delete' => 'DELETE FROM `%s` WHERE `key` = ?',
        'modify' => 'REPLACE INTO `%s` (`key`, `data`, `expiration`)
                VALUES(:key, :value, :expiration)'
    );
    private $keys = array();
    private $data = array();
    private $ttls = array();
    private $table_name;
    private $driver;

    public function __construct(\PDO $driver, $table_name)
    {
        register_shutdown_function(array($this, 'close'));
        $this->driver = $driver;
        $this->table_name = $table_name;

        //GC
        $driver->exec($this->getQuery('gc'));

        foreach ($driver->query($this->getQuery('index')) as $row) {
            $this->keys[$row['key']] = 1;
        }
    }

    public function getQuery($query)
    {
        if (!isset(static::$queries[$query])) {
            throw new \OutOfBoundsException('Query not set: ' . $query);
        }
        return sprintf(static::$queries[$query], $this->table_name);
    }

    public function getStatement($query)
    {
        return $this->driver->prepare($this->getQuery($query));
    }

    public function has($key)
    {
        return array_key_exists($key, $this->keys) && $this->keys[$key] != 'r';
    }

    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \OutOfBoundsException('Key not found: ' . $key);
        }
        if (!array_key_exists($key, $this->data)) {
            $statement = $this->getStatement('select');
            $statement->execute(array($key));
            if ($statement->rowCount() == 0) {
                //the key was deleted during an other request...
                throw new \OutOfBoundsException('Key not found: ' . $key);
            }
            $this->data[$key] = unserialize($statement->fetchColumn(0));
        }
        return $this->data[$key];
    }

    public function store($key, $data, $ttl)
    {
        $this->keys[$key] = 'm';
        $this->data[$key] = $data;
        $this->ttls[$key] = $ttl;
    }

    public function remove($key)
    {
        if ($key !== false) {
            $this->keys[$key] = 'r';
            unset($this->data[$key]);
            unset($this->ttls[$key]);
        }
    }

    public function close()
    {
        $save = false;
        if (in_array('r', $this->keys)) {
            $save = true;
            $delete_statement = $this->getStatement('delete');
        }
        if (in_array('m', $this->keys)) {
            $save = true;
            $modify_statement = $this->getStatement('modify');
        }

        if ($save) {
            $this->driver->beginTransaction();
            foreach ($this->keys as $key => $state) {
                if ($state == 'm') {
                    $ttl = $this->ttls[$key];
                    $array = array(
                        'key'        => $key,
                        'expiration' => date('Y-m-d H:i:s', time() + $ttl),
                        'value'      => serialize($this->data[$key])
                    );
                    $modify_statement->execute($array);
                } elseif ($state == 'r') {
                    $delete_statement->execute(array($key));
                }
            }
            $this->driver->commit();
        }
    }

}