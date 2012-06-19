<?php

namespace Miny\Database\Builder\Parts;

class Field {

    public $name;
    public $table;
    public $alias;
    public $type;

    public function __construct($table, $name, $type) {

        $this->table = $table;
        $this->name = $name;
        $this->type = $type;
    }

}