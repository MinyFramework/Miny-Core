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

class Query implements \IteratorAggregate
{
    private $table;
    private $with;
    private $columns;
    private $where;
    private $where_params = array();
    private $having;
    private $having_params = array();
    private $order;
    private $group;
    private $limit;
    private $offset;
    private $lock = false;

    public function __construct(Table $table)
    {
        $this->table = $table;
        $this->manager = $table->manager;
    }

    public function with()
    {
        $this->with = func_get_args();
        return $this;
    }

    public function select()
    {
        $this->columns = func_get_args();
        return $this;
    }

    public function where($condition)
    {
        $condition = '(' . $condition . ')';
        $params = func_get_args();
        array_shift($params);
        if (is_null($this->where)) {
            $this->where = $condition;
            $this->where_params = $params;
        } else {
            $this->where .= ' AND ' . $condition;
            $this->where_params = array_merge($this->where_params, $params);
        }
        return $this;
    }

    public function having($condition)
    {
        $condition = '(' . $condition . ')';
        $params = func_get_args();
        array_shift($params);
        if (is_null($this->having)) {
            $this->having = $condition;
            $this->having_params = $params;
        } else {
            $this->having .= ' AND ' . $condition;
            $this->having_params = array_merge($this->having_params, $params);
        }
        return $this;
    }

    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function group($group)
    {
        $this->group = $group;
        return $this;
    }

    public function lock($lock = true)
    {
        $this->lock = $lock;
        return $this;
    }

    public function __toString()
    {
        return $this->getQuery();
    }

    public function getQuery()
    {
        $table = $this->table->getTableName();
        $descriptor = $this->table->descriptor;
        $table_name = $table;
        $primary_key = $descriptor->primary_key;
        if (!empty($this->with)) {
            $columns = $this->columns ? : $this->table->descriptor->fields;
            foreach ($columns as $k => $name) {
                $columns[$k] = $table_name . '.' . $name . ' as ' . $table_name . '_' . $name;
            }
            foreach ($this->with as $name) {
                $relation = $this->table->descriptor->getRelation($name);
                $related = $this->table->getRelatedTable($name);
                $related_table = $related->getTableName();
                $related_descriptor = $related->descriptor;
                $related_primary = $related_descriptor->primary_key;

                foreach ($related_descriptor->fields as $related_field) {
                    $columns[] = $related_table . '.' . $related_field . ' as ' . $related_table . '_' . $related_field;
                }

                $join_pattern = ' LEFT JOIN %1$s ON (%1$s.%2$s = %3$s.%4$s)';
                $foreign_key = $this->table->getForeignKey($name);

                if ($relation->getType == Relation::MANY_MANY) {
                    $join_table = $this->table->getJoinTable($name);

                    $table_join_field = $this->table->getForeignKey($descriptor->name);

                    $table .= sprintf($join_pattern, $join_table, $table_join_field, $table_name, $primary_key);
                    $table .= sprintf($join_pattern, $related_table, $related_primary, $join_table, $foreign_key);
                } else {
                    $table .= sprintf($join_pattern, $related_table, $related_primary, $table_name, $foreign_key);
                }
            }
        } else {
            $columns = $this->columns ? : array('*');
        }
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $columns), $table);
        if (!is_null($this->where)) {
            $sql .= ' WHERE ' . $this->where;
        }
        if (!is_null($this->group)) {
            $sql .= ' GROUP BY ' . $this->group;
        }
        if (!is_null($this->having)) {
            $sql .= ' HAVING ' . $this->having;
        }
        if (!is_null($this->order)) {
            $sql .= ' ORDER BY ' . $this->order;
        }
        if (!is_null($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
            if (!is_null($this->offset)) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        $sql .= $this->lock ? ' FOR UPDATE' : '';
        return $sql;
    }

    public function execute()
    {
        $stmt = $this->table->manager->connection->prepare($this->getQuery());
        $i = 0;
        foreach ($this->where_params as $param) {
            $stmt->bindValue(++$i, $param);
        }
        foreach ($this->having_params as $param) {
            $stmt->bindValue(++$i, $param);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();
        if (empty($rows)) {
            return false;
        }
        if (empty($this->with)) {
            if (count($rows) == 1) {
                return new Row($this->table, current($rows));
            } else {
                $return = array();
                foreach ($rows as $row) {
                    $return[] = new Row($this->table, $row);
                }
                return $return;
            }
        } else {
            $table_fields = array();
            $relations_fields = array();
            $table = $this->table->descriptor->name;
            $pk_field = $this->table->descriptor->primary_key;
            foreach ($this->table->descriptor->fields as $name) {
                $table_fields[$table] = $table . '_' . $name;
            }
            foreach ($this->with as $name) {
                $relations_fields[$name] = array();
                foreach ($this->table->getRelatedTable($name)->descriptor->fields as $field) {
                    $relations_fields[$name] = $name . '_' . $field;
                }
            }
            $return = array();
            $last_pk = NULL;
            $relation_last_pks = array();
            $relations = array();
            foreach ($rows as $row) {
                if ($last_pk != $row[$table_fields[$pk_field]]) {
                    $rowdata = array();
                    foreach ($table_fields as $field => $alias) {
                        if (isset($row[$alias])) {
                            $rowdata[$field] = $row[$alias];
                        }
                    }
                    $last_pk = $rowdata[$pk_field];
                    $return[$last_pk] = new Row($rowdata);
                    $relations[$last_pk] = array();
                }
                foreach ($this->with as $name) {
                    $relation = $this->table->descriptor->getRelation($name);
                    $related = $relation->getTable();
                    $relation_pk = $related->getPrimaryKey();
                    $relation_pk_alias = $relations_fields[$relation_pk];

                    if (!isset($relations[$last_pk][$name])) {
                        $relations[$last_pk][$name] = array();
                    }

                    if (!isset($relation_last_pks[$name]) || $relation_last_pks[$name] != $row[$relation_pk_alias]) {
                        $relation_last_pks[$name] = $row[$relation_pk_alias];
                        $relation_data = array();
                        foreach ($relations_fields[$name] as $field => $alias) {
                            if (isset($row[$alias])) {
                                $relation_data[$field] = $row[$alias];
                            }
                        }
                        if ($relation->getType() == Relation::BELONGS_TO) {
                            $relations[$last_pk][$name] = new Row($relation_data);
                        } else {
                            $relations[$last_pk][$name][$row[$relation_pk_alias]] = new Row($relation_data);
                        }
                    }
                }
            }
            foreach ($relations as $pk => $array) {
                foreach ($array as $name => $data) {
                    $return[$pk]->$name = $data;
                }
            }
            return $return;
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->execute());
    }

}