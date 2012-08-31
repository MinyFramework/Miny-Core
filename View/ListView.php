<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

class ListView extends View
{
    public $list = array();

    public function render()
    {
        $output = '';
        foreach ($this->list as $item) {
            if (!is_array($item)) {
                $item = array(
                    'item'      => $item
                );
            }
            $this->vars = $item + $this->vars;
            $output .= parent::render();
        }
        return $output;
    }

}