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
        'select' => 'SELECT `data` FROM `%s` WHERE `key` = :key',
        'delete' => 'DELETE FROM `%s` WHERE `key` = :key',
        'modify' => 'REPLACE INTO `%s` (`key`, `data`, `expiration`)
                VALUES(:key, :value, :expiration)'
    );

    public static function getQuery($query)
    {
        if (!isset(static::$queries[$query])) {
            throw new \OutOfBoundsException('Query not set: ' . $query);
        }
        return static::$queries[$query];
    }

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
        $gc_query = static::getQuery('gc');
        $driver->exec(sprintf($gc_query, $table_name));

        $index_query = static::getQuery('index');
        $result = $driver->query(sprintf($index_query, $table_name));

        foreach ($result as $row) {
            $this->keys[$row['key']] = 1;
        }
    }

    public function exists($key)
    {
        return array_key_exists($key, $this->keys) && $this->keys[$key] != 'r';
    }

    public function get($key)
    {
        if (!$this->exists($key)) {
            throw new \OutOfBoundsException('Key not found: ' . $key);
        }
        if (!array_key_exists($key, $this->data)) {
            $select_query = static::getQuery('select');
            $select_query = sprintf($select_query, $this->table_name);
            $statement = $this->driver->prepare($select_query);
            $statement->bindValue('key', $key);
            $statement->execute();
            $temp = $statement->fetch();
            $this->data[$key] = unserialize($temp['data']);
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
        $db = $this->driver;
        $statements = array();
        if (in_array('r', $this->keys)) {
            $save = true;

            $delete_query = static::getQuery('delete');
            $delete_query = sprintf($delete_query, $this->table_name);

            $statements['r'] = $db->prepare($delete_query);
        }
        if (in_array('m', $this->keys)) {
            $save = true;
            $replace_query = static::getQuery('modify');
            $replace_query = sprintf($replace_query, $this->table_name);
            $statements['m'] = $db->prepare($replace_query);
        }

        if ($save) {
            $db->beginTransaction();
            foreach ($this->keys as $key => $state) {
                if ($state == 1) {
                    continue;
                }
                $statement = $statements[$state];
                switch ($state) {
                    case 'm':
                        $data = serialize($this->data[$key]);
                        $ttl = $this->ttls[$key];
                        $expire = date('Y-m-d H:i:s', time() + $ttl);

                        $statement->bindValue(':value', $data);
                        $statement->bindValue(':expiration', $expire);

                    case 'r':
                        $statement->bindValue(':key', $key);
                        break;
                }
                $statement->execute();
            }
            $db->commit();
        }
    }

}