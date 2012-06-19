<?php

namespace Miny\Database\Builder;

use Miny\Database\Table;
use Miny\Database\Builder\Parts\Field;
use Miny\Database\Builder\Parts\Parameter;
use Miny\Database\Builder\Parts\Expression;
use Miny\Database\Builder\Parts\QueryFunction;
use Miny\Database\Builder\Abstracts\ExtendedQueryBase;

class SelectQuery extends ExtendedQueryBase {

    private $fields = array();

    public function fields($fields, $overwrite = false) {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if ($overwrite) {
            $this->fields = $fields;
        } else {
            $this->fields = array_merge($fields, $this->fields);
        }
        return $this;
    }

    public function getFromTablesString(ExtendedQueryBase $query) {
        return sprintf($this->getSchema()->getPattern('from'), $this->getTablePart($query));
    }

    public function getSelectFieldsString(array $array, $table_alias = NULL) {
        $schema = $this->getSchema();
        $quoted_fields = array();

        foreach ($array as $table => $fields) {
            foreach ($fields as $alias => $field) {
                if (is_numeric($alias)) {
                    $alias = 'f' . $alias;
                }
                if (is_string($field)) {
                    $alias = NULL;
                    $field = $table . '.' . $field;
                }
                if (!is_null($table_alias)) {
                    $alias = $table_alias . '_' . $alias;
                }
                $quoted_fields[] = $this->getField($field, $alias);
            }
        }
        $string = implode(', ', $quoted_fields);
        return sprintf($schema->getPattern('select'), $string);
    }

    public function get() {
        $arr = $this->getQueryDetails($this);

        $fields = $this->getSelectFieldsString($arr['fields']);
        $from = $this->getFromTablesString($this);

        $where = $this->getWhereString($this->where);
        $groups = $this->getGroupByString($arr['group_by']);
        $having = $this->getHavingString($this->having);
        $order = $this->getOrderByString($arr['order_by']);
        $limit = $this->getLimitString($this->limit);
        return $fields . $from . $where . $groups . $having . $order . $limit;
    }

    private function getQueryDetails() {

        $array = array();
        $alias = $this->alias;
        if (in_array('*', $this->fields)) {
            $array['fields'][$alias] = $this->table->getFieldNames();
        }
        foreach ($this->fields as $key => $field) {
            if (!is_string($field) || $field != '*') {
                $array['fields'][$alias][$key] = $field;
            }
        }
        $array['group_by'][$alias] = $this->group;
        $array['order_by'][$alias] = $this->order;

        foreach ($this->joins as $alias => $arr) {
            list($query, $type, $expr) = $arr;
            $temp = $query->getQueryDetails();
            $array['fields'] = $array['fields'] + $temp['fields'];
            $array['group_by'] = $array['group_by'] + $temp['group_by'];
            $array['order_by'] = $array['order_by'] + $temp['order_by'];
        }
        return $array;
    }

}