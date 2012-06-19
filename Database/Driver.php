<?php

namespace Miny\Database;

class Driver extends \PDO {

    private $table_prefix;

    public function setPrefix($prefix) {
        $this->table_prefix = $prefix;
    }

    public function replacePrefix($sql) {
        if ($this->table_prefix) {
            $sql = str_replace('{prefix}', $this->table_prefix, $sql);
        }
        return $sql;
    }

    public function prepare($statement, $driver_options = array()) {
        $statement = $this->replacePrefix($statement);
        return parent::prepare($statement, $driver_options);
    }

    public function query($statement) {
        $statement = $this->replacePrefix($statement);
        return parent::query($statement);
    }

    public function exec($statement) {
        $statement = $this->replacePrefix($statement);
        return parent::exec($statement);
    }

}