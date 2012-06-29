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
 * @package   Miny/Config
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\Config\Drivers;

class SQLConfig implements \Miny\Config\iConfig
{
    protected static $queries = array(
        'load'   => 'SELECT `key`, `value` FROM `%s`',
        'delete' => 'DELETE FROM `%s` WHERE `key` = ?',
        'modify' => 'REPLACE INTO `%s` (`key`, `value`)
                VALUES(:key, :value)'
    );
    private $keys = array();
    private $data = array();
    private $table_name;
    private $driver;

    public function __construct(\PDO $driver, $table_name)
    {
        register_shutdown_function(array($this, 'close'));
        $this->driver = $driver;
        $this->table_name = $table_name;

        foreach ($driver->query($this->getQuery('load')) as $row) {
            $this->keys[$row['key']] = 1;
            $this->data[$row['key']] = $row['value'];
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

    public function exists($key)
    {
        return array_key_exists($key, $this->keys) && $this->keys[$key] != 'r';
    }

    public function get($key)
    {
        if (!$this->exists($key)) {
            throw new \OutOfBoundsException('Key not found: ' . $key);
        }
        return $this->data[$key];
    }

    public function store($key, $data, $ttl)
    {
        $this->keys[$key] = 'm';
        $this->data[$key] = array($data, $ttl);
    }

    public function remove($key)
    {
        if ($key !== false) {
            $this->keys[$key] = 'r';
            unset($this->data[$key]);
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
                    $modify_statement->bindValue('key', $key);
                    $modify_statement->bindValue('value', $this->data[$key]);
                    $modify_statement->execute($array);
                } elseif ($state == 'r') {
                    $delete_statement->execute(array($key));
                }
            }
            $this->driver->commit();
        }
    }

}