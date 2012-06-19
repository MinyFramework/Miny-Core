<?php

namespace Miny\Database\Builder;

class UpdateQuery extends Abstracts\ExtendedQueryBase {

    private $values = array();

    public function set($key, $value) {
        $this->values[$key] = $value;
    }

    public function get() {

    }

}