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

use \Miny\ORM\Manager;

class ORMCacheDriver implements \Miny\Cache\iCacheDriver
{
    private $keys = array();
    private $data = array();
    private $ttls = array();
    private $table;

    public function __construct(Manager $manager, $table_name)
    {
        register_shutdown_function(array($this, 'close'));
        $this->table = $manager->$table_name;

        $this->table->deleteRows('expiration < NOW()');
        foreach ($this->table as $row) {
            $this->keys[$row['id']] = 1;
        }
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
            $this->data[$key] = unserialize($this->table[$key]['data']);
        }
        return $this->data[$key];
    }

    public function store($key, $data, $ttl)
    {
        if (isset($this->keys[$key])) {
            if ($this->keys[$key] != 'a') {
                $this->keys[$key] = 'm';
            }
        } else {
            $this->keys[$key] = 'a';
        }
        $this->data[$key] = $data;
        $this->ttls[$key] = $ttl;
    }

    public function remove($key)
    {
        if (!isset($this->keys[$key])) {
            return;
        }
        if ($this->keys[$key] == 'a') {
            unset($this->keys[$key]);
        } else {
            $this->keys[$key] = 'r';
        }
        unset($this->data[$key]);
        unset($this->ttls[$key]);
    }

    public function close()
    {
        $save = (in_array('r', $this->keys) || in_array('m', $this->keys) || in_array('a', $this->keys));

        if ($save) {
            $db = $this->table->manager->connection;
            $db->beginTransaction();
            foreach ($this->keys as $key => $state) {
                switch ($state) {
                    case 'a':
                        $this->table->insert(array(
                            'id'        => $key,
                            'expiration' => date('Y-m-d H:i:s', time() + $this->ttls[$key]),
                            'data'       => serialize($this->data[$key])
                        ));
                        break;
                    case 'm':
                        $data = array(
                            'expiration' => date('Y-m-d H:i:s', time() + $this->ttls[$key]),
                            'data'       => serialize($this->data[$key])
                        );
                        $this->table->update($key, $data);
                        break;
                    case 'r':
                        $this->table->delete($key);
                        break;
                }
            }
            $db->commit();
        }
    }

}