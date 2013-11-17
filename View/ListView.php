<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

use Traversable;
use UnexpectedValueException;

class ListView extends View
{
    public $list = array();

    public function render()
    {
        $output = '';
        if (!is_array($this->list) && !$this->list instanceof Traversable) {
            throw new UnexpectedValueException('Cannot iterate over the given value.');
        }
        foreach ($this->list as $item) {
            if (!is_array($item)) {
                $item = array(
                    'item' => $item
                );
            }
            $variables = $item + $this->variables;
            $output .= parent::render($variables);
        }
        return $output;
    }

}
