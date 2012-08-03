<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

class Resources extends RouteCollection
{
    protected static $memberActions = array(
        'show'             => 'GET',
        'destroy'          => 'DELETE',
        'edit'             => 'GET',
        'update'           => 'PUT'
    );
    protected static $collectionActions = array(
        'index'    => 'GET',
        'new'      => 'GET',
        'create'   => 'POST'
    );
    protected $member_actions;
    protected $collection_actions;
    protected $name;
    private $singular_name;
    private $parameters;
    private $built = false;
    private $id_pattern;
    private $parent;
    private $resources = array();

    public static function singularize($name)
    {
        if (substr($name, -1, 1) == 's') {
            return substr($name, 0, -1);
        }
        return $name;
    }

    public function __construct($name, array $parameters = array())
    {
        if (!isset($parameters['controller'])) {
            $parameters['controller'] = $name;
        }
        $this->name = $name;
        $this->parameters = $parameters;
        $this->member_actions = static::$memberActions;
        $this->collection_actions = static::$collectionActions;
        $this->singular(self::singularize($name));
    }

    public function specify($pattern)
    {
        $this->id_pattern = $pattern;
        return $this;
    }

    public function setParent(Resources $resource)
    {
        $this->parent = $resource;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function hasParent()
    {
        return !is_null($this->parent);
    }

    public function addParameters(array $parameters)
    {
        $this->parameters = $parameters + $this->parameters;
    }

    public function addParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    public function singular($name)
    {
        $this->singular_name = $name;
        return $this;
    }

    public function getName()
    {
        if ($this->hasParent()) {
            return $this->getParent()->getName() . '_' . $this->name;
        }
        return $this->name;
    }

    public function getSingularName()
    {
        if ($this->hasParent()) {
            return $this->getParent()->getName() . '_' . $this->singular_name;
        }
        return $this->singular_name;
    }

    public function only()
    {
        $only = array_flip(func_get_args());
        $this->collection_actions = array_intersect_key($this->collection_actions, $only);
        $this->member_actions = array_intersect_key($this->member_actions, $only);
        return $this;
    }

    public function except()
    {
        $except = array_flip(func_get_args());
        $this->collection_actions = array_diff_key($this->collection_actions, $except);
        $this->member_actions = array_diff_key($this->member_actions, $except);
        return $this;
    }

    public function member($method, $name)
    {
        $this->member_actions[$name] = $method;
        return $this;
    }

    public function collection($method, $name)
    {
        $this->collection_actions[$name] = $method;
        return $this;
    }

    public function resource(Resources $resource)
    {
        $this->resources[] = $resource;
        $resource->setParent($this);
        return $resource;
    }

    private function build()
    {
        if ($this->built) {
            return;
        }
        $this->built = true;

        $this->generateMemberActions();
        $this->generateCollectionActions();

        foreach ($this->resources as $resource) {
            $resource->build();
            $this->merge($resource);
        }
    }

    public function getPathBase()
    {
        if (!$this->hasParent()) {
            return '';
        }
        $parent = $this->getParent();
        $path = $parent->getPathBase() . '/';
        if (!$parent instanceof Resource) {
            $path .= ':' . $parent->name . '_id/';
        }
        return $path;
    }

    protected function generateActions($actions, array $unnamed, $unnamed_route_name, $path)
    {
        $parameters = $this->parameters;
        $singular_name = $this->getSingularName();
        $parent = $this->getParent();
        foreach ($actions as $action => $method) {
            $parameters['action'] = $action;
            if (in_array($action, $unnamed)) {
                $name = $unnamed_route_name;
                $unnamed_route_name = NULL;
                $route = new Route($path, $method, $parameters);
            } else {
                $name = $action . '_' . $singular_name;
                $route = new Route($path . '/' . $action, $method, $parameters);
            }
            $route->specify('id', $this->id_pattern);
            if ($this->hasParent()) {
                $route->specify($parent->name . '_id', $parent->id_pattern);
            }
            $this->addRoute($route, $name);
        }
    }

    protected function generateMemberActions()
    {
        $unnamed = array('show', 'update', 'destroy');
        $this->generateActions($this->member_actions, $unnamed, $this->getSingularName(),
                $this->getPathBase() . $this->singular_name . '/:id');
    }

    protected function generateCollectionActions()
    {
        $unnamed = array('index', 'create');
        $this->generateActions($this->collection_actions, $unnamed, $this->getName(), $this->getPathBase() . $this->name);
    }

    public function getRoute($name)
    {
        $this->build();
        return parent::getRoute($name);
    }

    public function getIterator()
    {
        $this->build();
        return parent::getIterator();
    }

}