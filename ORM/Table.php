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
 * @package   Miny
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\ORM;

class Table implements \ArrayAccess, \IteratorAggregate
{
    public $manager;
    public $descriptor;
    public $related_tables = array();
    private $loaded_records = array();

    public function __construct(Manager $manager, TableDescriptor $descriptor)
    {
        $this->manager = $manager;
        $this->descriptor = $descriptor;
        foreach ($descriptor->relations as $relation) {
            $this->related_tables[$relation] = $this->manager->$relation;
        }
    }

    public function newRow(array $data = array())
    {
        return new Row($this, $data);
    }

    public function getTableName()
    {
        return sprintf($this->manager->table_format, $this->descriptor->name);
    }

    public function getPrimaryKey()
    {
        return $this->descriptor->primary_key;
    }

    public function getForeignKey($referenced)
    {
        return sprintf($this->manager->foreign_key, $referenced);
    }

    public function getRelatedTable($relation)
    {
        return $this->descriptor->getRelation($relation)->getTable();
    }

    public function getJoinTable($relation)
    {
        $table = $this->descriptor->getRelation($relation)->getTable();
        return sprintf($this->manager->table_format, $this->descriptor->name . '_' . $table->descriptor->name);
    }

    public function getRelated($relation, $key)
    {
        $related = $this->getRelatedTable($relation);
        return $related[$key];
    }

    public function save(Row $row, $force_insert = false)
    {
        if (!isset($row[$this->getPrimaryKey()]) || $force_insert) {
            $this->insert($row->toArray());
        } else {
            $this->update($row[$this->getPrimaryKey()], $row->toArray());
        }
    }

    public function insert(array $data)
    {
        $data = array_intersect_key($data, array_flip($this->descriptor->fields));
        $fields = array();
        foreach (array_keys($data) as $key) {
            $fields[$key] = ':' . $key;
        }
        $fields = implode(', ', array_keys($fields));
        $placeholders = implode(', ', $fields);

        $pattern = 'INSERT INTO %s (%s) VALUES (%s)';
        $sql = sprintf($pattern, $this->getTableName(), $fields, $placeholders);
        $stmt = $this->manager->connection->prepare($sql);
        $stmt->execute($data);
    }

    public function update($pk, array $data)
    {
        $data = array_intersect_key($data, array_flip($this->descriptor->fields));
        $fields = array();
        foreach (array_keys($data) as $key) {
            $fields[] = sprintf('SET %1$s = :%1$s', $key);
        }
        $pattern = 'UPDATE %s %s WHERE %s = :pk';
        $data['pk'] = $pk;

        $sql = sprintf($pattern, $this->getTableName(), implode(', ', $fields), $this->getPrimaryKey());
        $stmt = $this->manager->connection->prepare($sql);
        $stmt->execute($data);
    }

    public function delete($pk)
    {
        $condition = sprintf('%s = :pk', $this->getPrimaryKey());
        $this->deleteRows($condition, array('pk' => $pk));
    }

    public function deleteRows($condition, array $parameters = NULL)
    {
        $pattern = 'DELETE FROM %s WHERE %s';

        $sql = sprintf($pattern, $this->getTableName(), $condition);
        $stmt = $this->manager->connection->prepare($sql);
        $stmt->execute($parameters);
    }

    public function offsetExists($offset)
    {
        return $this->offsetGet($offset) !== false;
    }

    public function offsetGet($offset)
    {
        if (!isset($this->loaded_records[$offset])) {
            $query = new Query($this);
            $condition = sprintf('%s = ?', $this->descriptor->primary_key);
            $this->loaded_records[$offset] = $query->where($condition, $offset)->execute();
        }
        return $this->loaded_records[$offset];
    }

    public function offsetSet($offset, $row)
    {
        if ($row instanceof Row) {
            if ($row->getTable() != $this) {
                throw new \InvalidArgumentException('Cannot save row: table mismatch.');
            }
        } elseif (is_array($row)) {
            $row = new Row($this, $row);
        } else {
            throw new \InvalidArgumentException('Value should be a Row or an array');
        }

        $row[$this->getPrimaryKey()] = $offset;
        $row->save();
    }

    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    public function getIterator()
    {
        $query = new Query($this);
        $this->loaded_records = $query->execute();
        return new \ArrayIterator($this->loaded_records);
    }

}