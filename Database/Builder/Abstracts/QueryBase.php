<?php

namespace Miny\Database\Builder\Abstracts;

use Miny\Database\Schema\iSchema;
use Miny\Database\Table;
use Miny\Database\Builder\Parts\Field;
use Miny\Database\Builder\Parts\Parameter;
use Miny\Database\Builder\Parts\Expression;
use Miny\Database\Builder\Parts\QueryFunction;
use Miny\Database\Builder\Abstracts\ExtendedQueryBase;

abstract class QueryBase {

    private static $schema_array = array();
    private static $default;

    public static function addSchema($name, iSchema $schema) {
        self::$schema_array[$name] = $schema;
        self::setDefaultSchema($name);
    }

    public static function setDefaultSchema($name) {
        if (isset(self::$schema_array[$name])) {
            self::$default = $name;
        }
    }

    private $schema;
    private $table;
    private $where;
    private $order;
    private $limit;

    public function __construct($table, $schema = NULL) {
        if (is_null($schema)) {
            if (is_null(self::$default)) {
                throw new \InvalidArgumentException('Name is not specified and no default is set.');
            }
            $schema = self::$default;
        }
        if (!isset(self::$schema_array[$schema])) {
            throw new \InvalidArgumentException('Schema not set: ' . $schema);
        }

        $this->table = Table::get($table);
        $this->schema = self::$schema_array[$schema];
    }

    public function getSchema() {
        return $this->schema;
    }

    public function getTablePart(ExtendedQueryBase $query) {
        $joins = $query->joins;
        $query_alias = $query->alias;
        $schema = $this->getSchema();
        $string = $schema->quoteTable($query->table->getTableName(), $query_alias);
        foreach ($joins as $alias => $t) {
            list($query, $type, $join) = $t;

            $partial = $schema->getPattern($type . '_join');

            if ($join instanceof Expression) {
                $condition = sprintf($schema->getPattern('join_on'), $this->getExpressionString($join->expression));
            } elseif ($join instanceof Field) {
                $condition = sprintf($schema->getPattern('join_using'), $schema->quoteField($join->expression->name));
            } else {
                throw new \InvalidArgumentException('Invalid join condition for ' . $alias);
            }
            $string = sprintf($partial, $string, $schema->getTablePart($query), $condition);
        }
        return $string;
    }

    public function getOrderByString(array $array) {
        $schema = $this->getSchema();
        $quoted_fields = array();
        foreach ($array as $table_alias => $order_by) {
            foreach ((array) $order_by as $alias => $field) {
                if (is_numeric($alias)) {
                    $alias = 'f' . $alias;
                }
                list($field, $order) = $field;
                if ($field instanceof Field) {
                    $f = $this->quoteField($table_alias . '.' . $alias);
                    if ($order == 'desc') {
                        $f .= $schema->getPattern('order_desc');
                    } else {
                        $f .= $schema->getPattern('order_asc');
                    }
                    $quoted_fields[] = $f;
                }
            }
        }
        if (empty($quoted_fields)) {
            return;
        }
        $string = implode(', ', $quoted_fields);
        return sprintf($schema->getPattern('order_by'), $string);
    }

    public function getLimitString(array $limit = NULL) {
        if (is_null($limit)) {
            return;
        }
        list($num, $offset) = $limit;
        $schema = $this->getSchema();
        if (is_null($offset)) {
            return sprintf($schema->getPattern('limit'), $num);
        } else {
            return sprintf($schema->getPattern('limit_offset'), $num, $offset);
        }
    }

    public function getWhereString(Expression $expr = NULL) {
        return $this->getExpression('where', $expr);
    }

    public function getHavingString(Expression $expr = NULL) {
        return $this->getExpression('having', $expr);
    }

    public function getFunctionString(QueryFunction $function, $alias = NULL) {
        $schema = $this->getSchema();
        $string = $schema->getFunction($function->operator);
        $params = array();

        foreach ($function->operands as $arg) {
            $params[] = $this->getField($arg);
        }
        $string = sprintf($string, implode(', ', $params));
        if (!is_null($alias)) {
            $string .= sprintf($schema->getPattern('as'), $schema->quoteField($alias));
        }
        return $string;
    }

    private function getExpression($pattern, Expression $expr = NULL) {
        if (is_null($expr)) {
            return;
        }
        $schema = $this->getSchema();
        $str = $this->getExpressionString($expr);
        return sprintf($schema->getPattern($pattern), $str);
    }

    public function getExpressionString(Expression $expression, $query_alias = NULL) {
        $schema = $this->getSchema();
        $pattern = $schema->getOperator($expression->operator);
        $params = array();
        foreach ($expression->operands as $operand) {
            if ($operand instanceof Expression) {
                $params[] = '(' . $this->getExpressionString($operand) . ')';
            } else {
                $params[] = $this->getField($operand, $query_alias); //TODO: ide kéne alias, tábla-alias (Field módosítása)
            }
        }
        return vsprintf($pattern, $params);
    }

    public function getField($field, $alias = NULL) {
        $schema = $this->getSchema();
        if (is_numeric($field)) {
            return $field;
        } elseif (is_string($field)) {
            if ($field[0] == ':') {
                return $field;
            } else {
                return $schema->quoteField($field, $alias);
            }
        } elseif (is_bool($field)) {
            return $field ? 'TRUE' : 'FALSE';
        } elseif (is_null($field)) {
            return 'NULL';
        } elseif ($field instanceof QueryFunction) {
            return $this->getFunctionString($field, $alias);
        } elseif ($field instanceof Field) {
            return $schema->quoteField($field->table->name . '.' . $field->name, $alias);
        } elseif ($field instanceof ExtendedQueryBase) {
            $string = '(' . $field->get() . ')';
            if (!is_null($alias)) {
                $string .= sprintf($schema->getPattern('as'), $schema->quoteField($alias));
            }
            return $string;
        } elseif ($field instanceof Parameter) {
            return ':' . $field->name;
        }
        throw new \InvalidArgumentException('Invalid argument type: ' . gettype($field));
    }

    public function setTable(Table $table) {
        $this->table = $table;
        return $this;
    }

    public function where($where) {
        if (!$where instanceof Expression) {
            $ref = new \ReflectionClass('\Miny\Database\Builder\Parts\Expression');
            $where = $ref->newInstanceArgs(func_get_args());
        }
        $this->where = $where;
        return $this;
    }

    public function order(Field $col, $order) {
        $this->order[] = array($col, $order);
        return $this;
    }

    public function limit($limit, $offset = 0) {
        $this->limit[] = array($limit, $offset);
        return $this;
    }

    public function __get($key) {
        return $this->$key;
    }

    public abstract function get();
}