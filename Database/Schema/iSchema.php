<?php

namespace Miny\Database\Schema;

interface iSchema {

    public function quoteField($field, $alias = NULL);

    public function quoteTable($table, $alias = NULL);

    public function getPattern($name);

    public function getFunction($name);

    public function getOperator($name);

    public function getTypes();

    public function getType($type, $length = NULL);

}