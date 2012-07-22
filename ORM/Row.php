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
 * @package   Miny/ORM
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\ORM;

class Row implements \ArrayAccess, \IteratorAggregate
{
    private $table;
    private $data;
    private $related;
    private $changed = array();

    public function __construct(Table $table, array $data = array())
    {
        $this->table = $table;
        $this->data = $data;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getChangedValues()
    {
        $return = array();
        foreach ($this->changed as $key) {
            $return[$key] = $this->data[$key];
        }
        return $return;
    }

    public function __get($related)
    {
        if (!isset($this->related[$related])) {
            $foreign_key = $this->table->getForeignKey($related);
            if (!isset($this->data[$foreign_key])) {
                throw new \OutOfBoundsException('Foreign key is not set: ' . $foreign_key);
            }
            $this->related[$related] = $this->table->getRelated($related, $this->data[$foreign_key]);
        }
        return $this->related[$related];
    }

    public function save($force_insert = false)
    {
        $this->table->save($this, $force_insert);
    }

    public function delete()
    {
        $this->table->delete($this->data[$this->table->getPrimaryKey()]);
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->data[$offset])) {
            throw new \OutOfBoundsException('Key not set: ' . $offset);
        }
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (in_array($offset, $this->table->descriptor->fields)) {
            if (!in_array($offset, $this->changed)) {
                $this->changed[] = $offset;
            }
        }
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        $this->offsetSet($offset, NULL);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function toArray()
    {
        return $this->data;
    }

}