<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\View;

use Miny\Extendable;
use RuntimeException;

class View extends Extendable
{
    private $path;
    private $format;

    public function __construct($path, $format = NULL)
    {
        $this->path = $path;
        $this->format = $format;
    }

    public function get($file = NULL)
    {
        return new ViewDescriptor($file, $this);
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function getFormat($format = NULL)
    {
        $format = $format ? : $this->format;
        if ($format) {
            return '.' . $format;
        }
    }

    public function filter($data, $filter)
    {
        $method = 'filter_' . $filter;
        return $this->$method($data);
    }

    private function getTemplatePath($template, $format)
    {
        $filename = $template . $this->getFormat($format);
        $file = $this->path . '/' . $filename . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Template not found: ' . $filename);
        }
        return $file;
    }

    public function render($file, $format = NULL, array $params = array())
    {
        extract($params, EXTR_SKIP | EXTR_REFS);
        ob_start();
        include $this->getTemplatePath($file, $format);
        return ob_get_clean();
    }

}