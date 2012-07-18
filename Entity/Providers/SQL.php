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

namespace Miny\Entity\Providers;

use \Miny\Entity\EntityProvider;

class SQL extends EntityProvider
{
    private $driver;
    private $table_name;
    private $modified_entities = array();

    public function __construct(\PDO $driver, $class, $table_name)
    {
        $this->driver = $driver;
        $this->table_name = $table_name;
        parent::__construct($class);
        register_shutdown_function(array($this, 'save'));
    }

    public function get($key)
    {
        try {
            $entity = parent::get($key);
        } catch (\OutOfBoundsException $e) {
            $entity = $this->create();
            $sql = sprintf('SELECT * FROM `%s` WHERE `%s` = ?', $this->table_name, $entity::getKeyName());
            $stmt = $this->driver->prepare($sql);
            $stmt->bindValue(1, $key);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                throw $e;
            }
            foreach($stmt->fetch() as $key => $value) {
                $entity->$key = $value;
            }
            parent::add($entity);
        }
        return $entity;
    }

    public function add(Entity $ent)
    {
        $key = $ent->getKey();
        $state = $this->has($key) ? 'm' : 'a';
        $this->modified_users[$key] = $state;
        return parent::add($ent);
    }

    public function remove($key)
    {
        if (parent::remove($key)) {
            if (isset($this->modified_entities[$key]) && $this->modified_entities[$key] == 'a') {
                unset($this->modified_entities[$key]);
            } else {
                $this->modified_entities[$key] = 'r';
            }
            return true;
        }
    }

    public function has($key)
    {
        if (parent::has($key)) {
            return true;
        }
        try {
            $this->get($key);
            return true;
        } catch (\OutOfBoundsException $e) {
            return false;
        }
    }

    public function save()
    {
        if (empty($this->modified_users)) {
            return;
        }
        $this->driver->beginTransaction();
        foreach ($this->modified_users as $key => $state) {
            switch ($state) {
                case 'a':
                case 'm':
                    $this->saveEntity(parent::get($key));
                    break;
                case 'r':
                    $this->deleteEntity($key);
                    break;
            }
        }
        $this->driver->commit();
    }

    public function saveEntity(Entity $ent)
    {
        $fields = array();
        foreach ($ent->getFieldList() as $name) {
            $fields[':' . $name] = '`' . $name . '`';
        }
        $fields = implode(', ', $fields);
        $values = implode(', ', array_keys($fields));

        $sql = sprintf('REPLACE INTO `%s` (%s) VALUES (%s)', $this->table_name, $fields, $values);
        $this->driver->prepare($sql)->execute($ent->toArray());
    }

    private function deleteEntity($key)
    {
        $sql = sprintf('DELETE FROM `%s` WHERE `%s` = ?', $this->table_name, $key);
        $stmt = $this->driver->prepare($sql);
        $stmt->bindValue(1, $key);
        $stmt->execute();
    }

}