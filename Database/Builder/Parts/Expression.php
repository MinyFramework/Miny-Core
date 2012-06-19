<?php

namespace Miny\Database\Builder\Parts;

class Expression {

    public $operator;
    public $operands;

    public function __construct($operator) {
        $operands = func_get_args();
        array_shift($operands);

        $this->operator = $operator;
        $this->operands = $operands;
    }

}