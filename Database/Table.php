<?php

namespace Miny\Database;

class Table {

    private static $tables = array();

    public static function get($table) {
        if (!isset(self::$tables[$table])) {
            throw new \OutOfBoundsException('Table not set: ' . $table);
        }
        return self::$tables[$table];
    }

    /**
     * The table name
     * @var string
     */
    private $name;

    /**
     * The fields in the table.
     * Format: [field name => field type]
     *
     * @var array
     */
    private $fields;

    /**
     * The name of the primary key field.
     * @var string
     */
    private $primary_key;

    /**
     * @link http://wiki.answers.com/Q/What_is_the_plural_of_index
     * @var array Array of indexes and unique fields
     */
    private $indexes = array();

    public function __construct($table, array $fields, $primary_key = NULL, $indexes = array()) {

        if (!is_array($indexes)) {
            $indexes = array($indexes);
        }

        foreach ($fields as $name => $type) {
            if ($type == 'pk') {
                $primary_key = $name;
            }
            $this->fields[$name] = $type;
        }
        if (!isset($fields[$primary_key])) {
            throw new \InvalidArgumentException('Invalid primary key for table: ' . $table);
        }

        $index_array = array(
            'index' => array(),
            'unique' => array()
        );

        foreach ($indexes as $type => $index) {
            if (is_array($index)) {
                $index_type = array_shift($index);
                if (empty($index)) {
                    throw new \InvalidArgumentException('Invalid index given for table: ' . $field);
                }
                if (!isset($index_array[$index_type])) {
                    throw new \InvalidArgumentException('Invalid index type: ' . $type);
                }
                foreach ($index as $field) {
                    if (!isset($fields[$field])) {
                        throw new \InvalidArgumentException('Invalid index: ' . $field);
                    }
                }
            } else {
                if (!isset($fields[$index])) {
                    throw new \InvalidArgumentException('Invalid index: ' . $index);
                }
                if (!isset($index_array[$type])) {
                    throw new \InvalidArgumentException('Invalid index type: ' . $type);
                }
            }
            $index_array[$index_type][] = $index;
        }

        $this->name = $table;
        $this->primary_key = $primary_key;
        $this->indexes = $index_array;

        self::$tables[$table] = $this;
    }

    public function hasField($name) {
        return isset($this->fields[$name]);
    }

    public function getFieldType($name) {
        if (!isset($this->fields[$name])) {
            throw new \OutOfBoundsException('Field not set: ' . $name);
        }
        return $this->fields[$name];
    }

    public function getFieldNames() {
        return array_keys($this->fields);
    }

    public function getIndexes() {
        return $this->indexes;
    }

    public function getTableName() {
        return $this->name;
    }

    public function getPrimaryKey() {
        return $this->primary_key;
    }

}