<?php

//TODO: nagy része a Builderbe való

namespace Miny\Database\Schema;

class MySQLSchema implements iSchema {

    private $types = array(
        'pk' => array(
            'type_str'       => 'int(%d) NOT NULL PRIMARY KEY',
            'default_length' => 11
        ),
        'int'            => array(
            'type_str'       => 'int(%d)',
            'default_length' => 11
        ),
        'string'         => array(
            'type_str'       => 'varchar(%d)',
            'default_length' => 255
        ),
        'text'           => array(
            'type_str' => 'TEXT'
        ),
        'date'     => array()
    );
    private $functions = array(
        'count'    => 'COUNT(%s)',
        'max'      => 'MAX(%s, %s)',
        'min'      => 'MIN(%s, %s)',
        'average'  => 'AVG(%s)',
        'now'      => 'NOW()',
    );
    private $operators = array(
        'and'        => '%s AND %s',
        'or'         => '%s OR %s',
        'xor'        => '%s XOR %s',
        '<'          => '%s < %s',
        '>'          => '%s > %s',
        '!='         => '%s != %s',
        '='          => '%s = %s',
        '>='         => '%s >= %s',
        '<='         => '%s <= %s',
        'isnull'     => 'IS NULL',
        'notnull'    => 'IS NOT NULL',
        'like'       => 'LIKE %s',
        'notlike'    => 'NOT LIKE %s',
        'between'    => 'BETWEEN %s AND %s',
        'nowbetween' => 'NOT BETWEEN %s AND %s',
    );
    private $patterns = array(
        'as'              => ' as %s',
        'delete'          => 'DELETE',
        'insert_into'     => 'INSERT INTO %s (%s) VALUES',
        'replace_into'    => 'REPLACE INTO %s (%s) VALUES',
        'select'          => 'SELECT %s',
        'update'          => 'UPDATE TABLE %s',
        'from'            => ' FROM %s',
        'where'           => ' WHERE %s',
        'having'          => ' HAVING %s',
        'limit'           => ' LIMIT %s',
        'limit_offset'    => ' LIMIT %s OFFSET %s',
        'left_join'       => ' (%s LEFT JOIN %s %s)',
        'left_outer_join' => ' (%s LEFT OUTER JOIN %s %s)',
        'join_on'         => ' ON(%s)',
        'join_using'      => ' USING(%s)',
        'group_by'        => ' GROUP BY %s',
        'order_by'        => ' ORDER BY %s',
        'order_asc'       => ' ASC',
        'order_desc'      => ' DESC',
    );

    public function quoteField($field, $alias = NULL) {
        $field = ltrim($field, '.');
        if (!is_null($alias) && $field != $alias) {
            $alias = sprintf($this->getPattern('as'), '`' . $alias . '`');
        }

        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $field = array_pop($parts);
            foreach ($parts as $key => $part) {
                if (empty($part)) {
                    unset($parts[$key]);
                } else {
                    $parts[$key] = '`' . $part . '`.';
                }
            }
        } else {
            $parts = array();
        }
        if ($field != '*') {
            $field = '`' . $field . '`';
        }
        return implode('', $parts) . $field . $alias;
    }

    public function quoteTable($table, $alias = NULL) {
        if (!is_null($alias)) {
            $alias = ' `' . $alias . '`';
        }
        $database = NULL;
        if (strpos($table, '.') !== false) {
            list($database, $table) = explode('.', $table);
            $database = '`' . $database . '`.';
        }

        if ($table != '*') {
            $table = '`' . $table . '`';
        }
        return $database . $table . $alias;
    }
    //TODO: ezeket ki lehet pakolni egy közös Schema ősbe
    public function getPattern($name) {
        if (!isset($this->patterns[$name])) {
            throw new \OutOfBoundsException('Pattern not found: ' . $name);
        }
        return $this->patterns[$name];
    }

    public function getFunction($name) {
        if (!isset($this->functions[$name])) {
            throw new \OutOfBoundsException('Function not found: ' . $name);
        }
        return $this->functions[$name];
    }

    public function getOperator($name) {
        if (!isset($this->operators[$name])) {
            throw new \OutOfBoundsException('Operator not found: ' . $name);
        }
        return $this->operators[$name];
    }

    public function getTypes() {
        return $this->types;
    }

    public function getType($type, $length = NULL) {
        $types = $this->types;
        if (!isset($types[$type])) {
            return $type;
        }
        if (is_null($length)) {
            if (isset($types[$type]['default_length'])) {
                $length = $types[$type]['default_length'];
            } else {
                return $types[$type]['type_str'];
            }
        }
        return sprintf($types[$type]['type_str'], $length);
    }

}