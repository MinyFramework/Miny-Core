<?php

namespace Miny\Database\Builder\Parts;

class Parameter {

    public $name;

    public function __construct($name) {
        $this->name = $name;
    }

}