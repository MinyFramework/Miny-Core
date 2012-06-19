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
 * @package   Miny/QueryBuilder/Abstracts
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\QueryBuilder\Abstracts;

use Miny\QueryBuilder\Parts\Field;
use Miny\QueryBuilder\Parts\Expression;

abstract class ExtendedQueryBase extends QueryBase {

    private $data = array(
        'table_names' => array(), // alias => name
        'group' => array(),
        'joins' => array(),
        'having' => NULL,
        'alias'  => NULL
    );

    public function __construct($table, $alias = NULL, $schema = NULL) {
        $this->setAlias($alias);
        parent::__construct($table, $schema);
    }

    public function getGroupByString(array $array) {
        $schema = $this->getSchema();
        $quoted_fields = array();
        foreach ($array as $table_alias => $group_by) {
            foreach ($group_by as $alias => $field) {
                if ($field instanceof Field) {
                    if (is_numeric($alias)) {
                        $alias = 'f' . $alias;
                    }
                    if ($field instanceof Field) {
                        $quoted_fields[] = $schema->quoteField($table_alias . '.' . $alias);
                    } elseif (is_string($field)) {
                        $quoted_fields[] = $schema->quoteField($table_alias . '.' . $alias);
                    }
                }
            }
        }
        if (empty($quoted_fields)) {
            return;
        }
        $string = implode(', ', $quoted_fields);
        return sprintf($schema->getPattern('group_by'), $string);
    }

    public function having($having) {
        if (!$having instanceof Expression) {
            $ref = new \ReflectionClass('\Miny\Database\Builder\Parts\Expression');
            $having = $ref->newInstanceArgs(func_get_args());
        }
        $this->data['having'] = $having;
        return $this;
    }

    public function group(Field $col) {
        $this->data['group'][] = $col;
        return $this;
    }

    public function setAlias($alias) {
        $this->data['alias'] = $alias;
        return $this;
    }

    public function join(ExtendedQueryBase $query, $type, $condition) {
        $alias = $query->alias;

        if (is_null($alias)) {
            $alias = $query->table->getTableName();
            $query->setAlias($alias);
        }

        $n_alias = $alias;
        $i = 1;
        while (isset($this->queries[$n_alias])) {
            $n_alias = $alias . $i++;
        }
        $query->setAlias($n_alias);
        $this->joins[$n_alias] = array($query, $type, $condition);
        $this->data['table_names'] = array_merge($query->table_names, $this->data['table_names']);
        return $this;
    }

    //TODO: magic get-ek kikaparása
    public function __get($key) {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } else {
            return parent::__get($key);
        }
    }

    public function getTableAlias($table) {
        if (!isset($this->data['table_names'][$table])) {
            throw new \OutOfBoundsException('Table alias not found: ' . $table);
        }
        return $this->data['table_names'][$table];
    }

}