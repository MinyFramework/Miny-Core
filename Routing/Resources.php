<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Routing;

use UnexpectedValueException;

class Resources extends RouteCollection
{
    protected static $memberActions = array(
        'show'    => 'GET',
        'destroy' => 'DELETE',
        'edit'    => 'GET',
        'update'  => 'PUT'
    );
    protected static $collectionActions = array(
        'index'  => 'GET',
        'new'    => 'GET',
        'create' => 'POST'
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

    /**
     *
     * @param string $name
     * @return string
     */
    public static function singularize($name)
    {
        if (substr($name, -1, 1) == 's') {
            return substr($name, 0, -1);
        }
        return $name;
    }

    /**
     *
     * @param string $name
     * @param array $parameters
     * @throws UnexpectedValueException
     */
    public function __construct($name, array $parameters = array())
    {
        if (!is_string($name)) {
            throw new UnexpectedValueException('Parameter "name" must be a string.');
        }
        if (!isset($parameters['controller'])) {
            $parameters['controller'] = $name;
        }
        $this->name = $name;
        $this->parameters = $parameters;
        $this->member_actions = static::$memberActions;
        $this->collection_actions = static::$collectionActions;
        $this->singular(self::singularize($name));
    }

    /**
     *
     * @param type $pattern
     * @return \Miny\Routing\Resources
     */
    public function specify($pattern)
    {
        $this->id_pattern = $pattern;
        return $this;
    }

    private function setParent(Resources $resource)
    {
        $this->parent = $resource;
    }

    private function getParent()
    {
        return $this->parent;
    }

    private function hasParent()
    {
        return !is_null($this->parent);
    }

    /**
     *
     * @param array $parameters
     */
    public function addParameters(array $parameters)
    {
        $this->parameters = $parameters + $this->parameters;
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     */
    public function addParameter($key, $value)
    {
        if (!is_string($key)) {
            throw new UnexpectedValueException('Parameter "key" must be a string.');
        }
        $this->parameters[$key] = $value;
    }

    /**
     *
     * @param string $name
     * @return \Miny\Routing\Resources
     * @throws UnexpectedValueException
     */
    public function singular($name)
    {
        if (!is_string($name)) {
            throw new UnexpectedValueException('Parameter "name" must be a string.');
        }
        $this->singular_name = $name;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        if ($this->hasParent()) {
            return $this->getParent()->getName() . '_' . $this->name;
        }
        return $this->name;
    }

    /**
     *
     * @return string
     */
    public function getSingularName()
    {
        if ($this->hasParent()) {
            return $this->getParent()->getName() . '_' . $this->singular_name;
        }
        return $this->singular_name;
    }

    /**
     *
     * @return \Miny\Routing\Resources
     */
    public function only()
    {
        $only = array_flip(func_get_args());
        $this->collection_actions = array_intersect_key($this->collection_actions, $only);
        $this->member_actions = array_intersect_key($this->member_actions, $only);
        return $this;
    }

    /**
     *
     * @return \Miny\Routing\Resources
     */
    public function except()
    {
        $except = array_flip(func_get_args());
        $this->collection_actions = array_diff_key($this->collection_actions, $except);
        $this->member_actions = array_diff_key($this->member_actions, $except);
        return $this;
    }

    /**
     *
     * @param string $method
     * @param string $name
     * @return \Miny\Routing\Resources
     */
    public function member($method, $name)
    {
        $this->member_actions[$name] = $method;
        return $this;
    }

    /**
     *
     * @param string $method
     * @param string $name
     * @return \Miny\Routing\Resources
     */
    public function collection($method, $name)
    {
        $this->collection_actions[$name] = $method;
        return $this;
    }

    /**
     *
     * @param \Miny\Routing\Resources $resource
     * @return \Miny\Routing\Resources
     */
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

        $this->generateCollectionActions();
        $this->generateMemberActions();

        foreach ($this->resources as $resource) {
            $resource->build();
            $this->merge($resource);
        }
    }

    /**
     *
     * @return string
     */
    public function getPathBase()
    {
        $path = '';
        if ($this->hasParent()) {
            $parent = $this->getParent();
            $path = $parent->getPathBase() . '/';
            if (!$parent instanceof Resource) {
                $path .= ':' . $parent->singular_name . '_id/';
            }
        }
        return $path . $this->name;
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
        $this->generateActions($this->member_actions, $unnamed, $this->getSingularName(), $this->getPathBase() . '/:id');
    }

    protected function generateCollectionActions()
    {
        $unnamed = array('index', 'create');
        $this->generateActions($this->collection_actions, $unnamed, $this->getName(), $this->getPathBase());
    }

    /**
     *
     * @param string $name
     * @return \Miny\Routing\Route
     */
    public function getRoute($name)
    {
        $this->build();
        return parent::getRoute($name);
    }

    /**
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->build();
        return parent::getIterator();
    }

}

