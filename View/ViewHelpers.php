<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Miny\View;

use Miny\Application\Application;
use Miny\Extendable;

class ViewHelpers extends Extendable
{
    private $application;
    private $router;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->router = $application->router;
    }

    public function file($file, array $params = array())
    {
        $view = $this->service('view_factory')->view($file);
        $view->setVariables($params);
        return $view->render();
    }

    public function service($service)
    {
        return $this->application->__get($service);
    }

    public function route($route, array $parameters = array())
    {
        return $this->router->generate($route, $parameters);
    }

    public function routeAnchor($route, $label, array $parameters = array(), array $args = array())
    {
        $url = $this->router->generate($route, $parameters);
        return $this->anchor($url, $label, $args);
    }

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
