<?php

namespace Miny\Database;

use Miny\Database\Table;

abstract class Record extends Model implements \ArrayAccess {

    const NOT_RELATED = 0;
    const HAS_ONE = 1;
    const BELONGS_TO = 2;
    const HAS_MANY = 3;
    const MANY_MANY = 4;
    const STATE_NEW = 0;
    const STATE_MODIFIED = 1;
    const STATE_UNMODIFIED = 2;

    private static $table_name;
    private static $relations;
    //private $validators = array();
    private $data = array();
    private $finder;
    private $in_relation_with = array();
    private $table;
    private $state;

    public static function get($connection = 'default') {
        return new RecordManager(get_called_class(), $connection);
    }

    public static function getRelations() {
        return static::$relations;
    }

    public static function hasRelation($relation) {
        return isset(static::$relations[$relation]);
    }

    public static function getRelation($relation) {
        if (!$this->hasRelation($relation)) {
            throw new \OutOfBoundsException('Relation not set: ' . $relation);
        }
        return static::$relations[$relaion];
    }

    public static function getRelationType($relation) {
        if (static::hasRelation($relation)) {
            $rel = static::getRelation($relation);
            return $rel['type'];
        }
        return self::NOT_RELATED;
    }

    public static function getRelationClass($relation) {
        $rel = static::getRelation($relation);
        return $rel['class'];
    }

    public static function getTableName() {
        return static::$table_name;
    }

    public function __construct($connection = 'default', $state = self::STATE_NEW) {
        parent::__construct($connection);
        $this->finder = static::get($connection);
        $this->table = Table::get(static::getTableName());
        $this->setState($state);
    }

    public function getState() {
        return $this->state;
    }

    public function setState($state) {
        $this->state = $state;
    }

    public function load(array $data, $prefix = '') {
        if (empty($data)) {
            return;
        }
        if (is_string($prefix) && $prefix !== '') {
            $pref_len = strlen($prefix);
            $filtered = array();
            foreach ($data as $key => $value) {
                if (substr($key, 0, $pref_len) == $prefix) {
                    $filtered[substr($key, $pref_len)] = $value;
                }
            }
            $data = $filtered;
        }
        $this->data = $data;
    }

    public function getTable() {
        return $this->table;
    }

    public function hasField($field) {
        return $this->getTable()->hasField($field) || array_key_exists($field, $this->data);
    }

    public function getField($field) {
        $table = $this->getTable();
        if (!$table->hasField($field)) {
            throw new \InvalidArgumentException('Field not set: ' . $field);
        }
        return $table->getField($field);
    }

    public function getFieldNames() {
        return array_keys($this->getFields());
    }

    public function getFields() {
        return $this->getTable()->fields;
    }

    public function __set($key, $value) {
        if ($this->hasField($key)) {
            $this->data[$key] = $value;
            $this->setState(self::STATE_MODIFIED);
        } else {
            try {
                $this->addRelated($key, $value);
            } catch (\InvalidArgumentException $e) {
                //Intentionally blank - silently drop assignment.
            }
        }
    }

    public function __get($key) {
        if ($this->hasField($key)) {
            return $this->data[$key];
        } elseif ($this->hasRelation($key)) {
            if (!isset($this->in_relation_with[$key])) {
                $this->loadRelated($key);
            }
            return $this->in_relation_with[$key];
        }
        throw new \OutOfBoundsException('Variable not set: ' . $key);
    }

    public function __isset($key) {
        return $this->hasField($key);
    }

    public function __unset($key) {
        if ($this->hasField($key)) {
            unset($this->data[$key]);
        }
    }

    public function getPrimaryKey() {
        return $this->getTable()->primary_key;
    }

    private function loadRelated($key) {
        if (!isset($this->in_relation_with[$key])) {
            $relation = static::getRelation($key);
            $fk = $this->data[$relation['foreign_key']];
            $related = $this->finder->getRelated($key);
            $related->findByPk($fk);
            $this->addRelated($key, $related);
        }
        return $this->in_relation_with[$key];
    }

    private function addRelated($name, Record $relation = NULL) {
        if (is_null($relation)) {
            $relation = $this->finder->getRelated($name);
        } else {
            $class = static::getRelationClass($name);
            if (!$relation instanceof $class) {
                throw new \InvalidArgumentException('Invalid relation: ' . $name);
            }
        }
        $this->in_relation_with[$name] = $relation;
    }

    public function valid() {
        $valid = true;
        //TODO: validátorok
        /* foreach ($this->validators as $field => $validators) {

          } */
        return $valid;
    }

    public function save() {

    }

    public function delete() {

    }

    public function offsetExists($offset) {
        return $this->hasField($offset);
    }

    public function offsetGet($offset) {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value) {
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset) {
        $this->__set($offset, NULL);
    }

}