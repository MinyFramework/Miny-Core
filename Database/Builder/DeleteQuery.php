<?php

namespace Miny\Database\Builder;

class DeleteQuery extends Abstracts\ExtendedQueryBase {

    public function get() {
        $schema = $this->getSchema();
        $string = $schema->getDeleteString();
        $string .= $schema->getFromTablesString($this);
        $string .= $schema->getWhereString($this->where);
        $string .= $schema->getOrderByString($this->order);
        $string .= $schema->getLimitString($this->limit);
        return $string;
    }

}