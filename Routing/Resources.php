<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Routing
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Routing;

class Resources extends RouteCollection
{
    private $name;
    private $singular_name;
    private $parameters;
    private $member_actions;
    private $collection_actions;
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
        $this->member_actions = $this->memberActions();
        $this->collection_actions = $this->collectionActions();
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
            $name = $this->getParent()->getName();
            $name .= '_' . $this->name;
            return $name;
        }
        return $this->name;
    }

    public function getSingularName()
    {
        if ($this->hasParent()) {
            $name = $this->getParent()->getName();
            $name .= '_' . $this->name;
            return $name;
        }
        return $this->singular_name;
    }

    protected function memberActions()
    {
        return array(
            'show'    => 'GET',
            'destroy' => 'DELETE',
            'edit'    => 'GET',
            'update'  => 'PUT'
        );
    }

    protected function collectionActions()
    {
        return array(
            'index'  => 'GET',
            'new'    => 'GET',
            'create' => 'POST'
        );
    }

    public function only()
    {
        $only = func_get_args();
        foreach (array_keys($this->collection_actions) as $action) {
            if (!in_array($action, $only)) {
                unset($this->collection_actions[$action]);
            }
        }
        foreach (array_keys($this->member_actions) as $action) {
            if (!in_array($action, $only)) {
                unset($this->member_actions[$action]);
            }
        }
        return $this;
    }

    public function except()
    {
        $except = func_get_args();
        foreach (array_keys($this->collection_actions) as $action) {
            if (in_array($action, $except)) {
                unset($this->collection_actions[$action]);
            }
        }
        foreach (array_keys($this->member_actions) as $action) {
            if (in_array($action, $except)) {
                unset($this->member_actions[$action]);
            }
        }
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

    public function getPath()
    {
        if ($this->hasParent()) {
            $parent = $this->getParent();
            $path = $parent->getPath() . '/';
            if (!$parent instanceof Resource) {
                $path .= ':' . $parent->name . '_id/';
            }
            return $path . $this->name;
        } else {
            return $this->name;
        }
    }

    protected function generateMemberActions()
    {
        $unnamed = array('show', 'update', 'destroy');
        $parameters = $this->parameters;
        $has_unnamed_action_route = false;
        $path = $this->getPath() . '/:id';
        foreach ($this->member_actions as $action => $method) {
            $parameters['action'] = $action;
            if (in_array($action, $unnamed)) {
                $name = NULL;
                if (!$has_unnamed_action_route) {
                    $name = $this->getSingularName();
                    $has_unnamed_action_route = true;
                }
                $route = new Route($path, $method, $parameters);
            } else {
                $name = $action . '_' . $this->getSingularName();
                $route = new Route($path . '/' . $action, $method, $parameters);
            }
            $this->addRoute($route, $name);
        }
    }

    protected function generateCollectionActions()
    {
        $unnamed = array('index', 'create');
        $parameters = $this->parameters;
        $has_unnamed_action_route = false;
        $path = $this->getPath();
        foreach ($this->collection_actions as $action => $method) {
            $parameters['action'] = $action;
            if (in_array($action, $unnamed)) {
                $name = NULL;
                if (!$has_unnamed_action_route) {
                    $name = $this->getName();
                    $has_unnamed_action_route = true;
                }
                $route = new Route($path, $method, $parameters);
            } else {
                $name = $action . '_' . $this->getSingularName();
                $route = new Route($path . '/' . $action, $method, $parameters);
            }
            $this->addRoute($route, $name);
        }
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