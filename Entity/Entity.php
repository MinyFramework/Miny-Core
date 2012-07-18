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
 * @package   Miny/Entity
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Entity;

class Entity implements \ArrayAccess, \IteratorAggregate
{
    private $provider;
    private $keys = array();

    public static function getKeyName()
    {
        return 'id';
    }

    public function __construct(array $data = array())
    {
        $this->keys = array_diff(array_keys(get_object_vars($this)), $this->privates());
        foreach ($data as $key => $value) {
            if ($this->__isset($key)) {
                $this->$key = $value;
            }
        }
    }

    protected function privates()
    {
        return array('provider', 'keys');
    }

    public function setProvider(EntityProvider $provider)
    {
        $this->provider = $provider;
    }

    private function checkProvider()
    {
        if (is_null($this->provider)) {
            throw new \BadMethodCallException('Entity provider is not set.');
        }
    }

    public function save()
    {
        $this->checkProvider();
        $this->provider->add($this);
    }

    public function remove()
    {
        $this->checkProvider();
        $this->provider->remove($this->getKey());
    }

    public function getKey()
    {
        $key = static::getKeyName();
        return $this->$key;
    }

    public function getFieldList()
    {
        return $this->keys;
    }

    public function checkField($name)
    {
        if (!in_array($name, $this->keys)) {
            throw new \InvalidArgumentException('Field not exists: ' . $name);
        }
    }

    public function equals(Entity $ent)
    {
        return $this->toArray() == $ent->toArray();
    }

    public function __isset($name)
    {
        return in_array($name, $this->keys);
    }

    public function __set($field, $value)
    {
        $this->checkField($field);
        $setter = 'set' . ucfirst(strtolower($field));
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->$field = $value;
        }
    }

    public function __get($field)
    {
        $this->checkField($field);
        $getter = 'get' . ucfirst(strtolower($field));
        return method_exists($this, $getter) ? $this->$getter() : $this->$field;
    }

    public function toArray()
    {
        return array_diff(get_object_vars($this), array_flip($this->privates()));
    }

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->__set($offset, NULL);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

}