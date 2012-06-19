<?php

namespace Miny\Database\Builder;

use Miny\Database\Builder\Parts\Table;

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