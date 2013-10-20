<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

use Miny\Extendable;

class ViewHelpers extends Extendable
{
    public function filter($data, $filter)
    {
        $method = 'filter_' . $filter;
        return $this->$method($data);
    }

    public function filter_escape($string)
    {
        return htmlspecialchars($string);
    }

    public function filter_json($var)
    {
        return json_encode($var);
    }

    public function anchor($url, $label, array $args = array())
    {
        $args['href'] = $url;
        return sprintf('<a%s>%s</a>', $this->arguments($args), $label);
    }

    public function arguments(array $args)
    {
        $arglist = '';
        foreach ($args as $name => $value) {
            $arglist .= sprintf(' %s="%s"', $name, $value);
        }
        return $arglist;
    }

}