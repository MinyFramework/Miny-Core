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
 * @package   Miny/QueryBuilder
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 *
 */

namespace Miny\QueryBuilder;

use Miny\QueryBuilder\Table;

class InsertQuery extends Abstracts\QueryBase {

    private $table;
    private $type;
    private $values = array();

    public function __construct(Table $table, $type = 'insert') {
        $this->table = $table;
        $this->type = $type;

        $fields = $table->fields;
        $this->field_count = count($fields);

        parent::__construct();
    }

    public function addValues() {
        $values = func_get_args();
        if (count($values) != $this->field_count) {
            throw new \InvalidArgumentException('Wrong number of arguments, must be ' . $this->field_count);
        }
        $this->values[] = $values;
    }

    public function getInsertValues(array $records) {
        $parts = array();
        foreach ($records as $record) {
            $arr = array();
            foreach ($record as $field) {
                $arr[] = $this->getField($field);
            }
            $parts[] = sprintf(' (%s)', implode(', ', $arr));
        }
        return implode(',', $parts);
    }

    public function getInsertString($type, Table $table) {
        $schema = $this->getSchema();
        if ($type == 'replace') {
            $pattern = $schema->getPattern('replace_into'); //'REPLACE INTO ';
        } else {
            $pattern = $schema->getPattern('insert_into'); //'INSERT INTO ';
        }
        $quoted_fields = array();
        foreach ($table->getFieldNames() as $field) {
            $quoted_fields[] = $schema->quoteField($field);
        }
        return sprintf($pattern, $schema->quoteTable($table->name), implode(', ', $quoted_fields));
    }

    public function get() {
        $string = $this->getInsertString($this->type, $this->table);
        if (empty($this->values)) {
            return $string . implode(', ', array_fill(0, $this->field_count, '?')); // placeholders
        } else {
            return $string . $this->getInsertValues($this->values);
        }
    }

}