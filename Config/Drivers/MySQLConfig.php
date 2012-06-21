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

class MySQLConfig implements \Miny\Config\iConfig {

    private $keys = array();
    private $data = array();
    private $table_name;
    private $driver;

    public function __construct(\PDO $driver, $table_name) {
        register_shutdown_function(array($this, 'close'));
        $this->driver = $driver;
        $this->table_name = $table_name;

        $sql = sprintf('SELECT `key`, `value` FROM `%s`', $table_name);

        foreach ($driver->query($sql) as $row) {
            $this->keys[$row['key']] = 1;
            $this->data[$row['key']] = $row['value'];
        }
    }

    public function exists($key) {
        return array_key_exists($key, $this->keys) && $this->keys[$key] != 'r';
    }

    public function get($key) {
        if (!$this->exists($key)) {
            throw new \OutOfBoundsException('Key not found: ' . $key);
        }
        return $this->data[$key];
    }

    public function store($key, $data, $ttl) {
        $this->keys[$key] = 'm';
        $this->data[$key] = array($data, $ttl);
    }

    public function remove($key) {
        if ($key !== false) {
            $this->keys[$key] = 'r';
            unset($this->data[$key]);
        }
    }

    public function close() {
        $save = false;
        $statements = array();
        if (in_array('r', $this->keys)) {
            $save = true;
            $sql = 'DELETE FROM `%s` WHERE `key` = :key';
            $sql = sprintf($sql, $this->table_name);
            $statements['r'] = $this->driver->prepare($sql);
        }
        if (in_array('m', $this->keys)) {
            $save = true;
            $sql = 'REPLACE INTO `%s` (`key`, `value`) VALUES(:key, :value)';
            $sql = sprintf($sql, $this->table_name);
            $statements['m'] = $this->driver->prepare($sql);
        }
        if (!$save) {
            return;
        }
        $this->driver->beginTransaction();
        foreach ($this->keys as $key => $state) {
            if ($state == 1)
                continue;
            $statement = $statements[$state];
            switch ($state) {
                case 'm':
                    $statement->bindValue(':value', $this->data[$key]);
                case 'r':
                    $statement->bindValue(':key', $key);
                    break;
            }
            $statement->execute();
        }
        $this->driver->commit();
    }

}