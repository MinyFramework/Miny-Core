<?php

namespace Miny\Widget;

interface iWidget {

    public function begin(array $params = array());
    
    public function end(array $params = array());
    
    public function run(array $params = array());

}