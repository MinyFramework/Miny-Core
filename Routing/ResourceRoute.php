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

class ResourceRoute
{
    private $name;
    private $singular_name;
    private $path_alias;
    private $collection_actions = array();
    private $member_actions = array();
    private $parent;
    private $singular;
    private $id_pattern = '(\d+)';
    private $resources = array();
    private $routes = array();
    private $singular_base_path = '';
    private $base_path = '';
    private $name_prefix = '';

    public function __construct($name, ResourceRoute $parent = NULL,
                                $singular = false)
    {
        $this->name = $name;
        $this->controller = $name;
        $this->singular = $singular;
        $this->parent = $parent;

        if (substr($name, -1) == 's') {
            $this->singular_name = substr($name, 0, -1);
        } else {
            $this->singular_name = $name;
        }
        $this->path_alias = $singular ? $this->singular_name : $name;

        if ($singular) {
            $this->collection_actions = array(
                'show'    => 'GET',
                'destroy' => 'DELETE',
                'edit'    => 'GET',
                'update'  => 'PUT',
                'new'     => 'GET',
                'create'  => 'POST'
            );
        } else {
            $this->member_actions = array(
                'show'    => 'GET',
                'destroy' => 'DELETE',
                'edit'    => 'GET',
                'update'  => 'PUT'
            );

            $this->collection_actions = array(
                'index'  => 'GET',
                'new'    => 'GET',
                'create' => 'POST'
            );
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSingularName()
    {
        return $this->singular_name;
    }

    public function getPathAlias()
    {
        return $this->path_alias;
    }

    public function path($name)
    {
        $this->path_alias = $name;
        return $this;
    }

    public function specify($pattern)
    {
        $this->id_pattern = $pattern;
        return $this;
    }

    public function controller($name)
    {
        $this->controller = $name;
        return $this;
    }

    public function singularForm($name)
    {
        if (!$this->singular) {
            $this->singular_name = $name;
        }
        return $this;
    }

    public function only()
    {
        $only = func_get_args();
        foreach ($this->collection_actions as $action => $method) {
            if (!in_array($action, $only)) {
                unset($this->collection_actions[$action]);
            }
        }
        foreach ($this->member_actions as $action => $method) {
            if (!in_array($action, $only)) {
                unset($this->member_actions[$action]);
            }
        }
        return $this;
    }

    public function except()
    {
        $except = func_get_args();
        foreach ($this->collection_actions as $action => $method) {
            if (in_array($action, $except)) {
                unset($this->collection_actions[$action]);
            }
        }
        foreach ($this->member_actions as $action => $method) {
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

    public function resource($name, $singular = true)
    {
        $resource = new ResourceRoute($name, $this, $singular);
        $this->resources[$name] = $resource;
        return $resource;
    }

    public function resources($name)
    {
        return $this->resource($name, $this, false);
    }

    private function getBaseString()
    {
        if (!$this->parent) {
            return '';
        }
        $parent = $this->parent;
        return $parent->getBasePath . ':' . $parent->getSingularName() . '_id/';
    }

    public function getBasePath()
    {
        if ($this->base_path == '') {
            $this->base_path = $this->getBaseString() . $this->path_alias;
        }
        return $this->base_path;
    }

    public function getSingularBasePath()
    {
        if ($this->singular_base_path == '') {
            $this->singular_base_path = $this->getBaseString();
            $this->singular_base_path .= $this->singular_name;
        }
        return $this->singular_base_path;
    }

    public function getNamePrefix()
    {
        if ($this->name_prefix == '') {
            if ($this->parent) {
                $this->name_prefix = $this->parent->getNamePrefix() . '_';
                $this->name_prefix .= $this->parent->getSingularName() . '_';
            }
        }
        return $this->name_prefix;
    }

    private function addIdPattern(Route $route, $name = NULL)
    {
        if ($this->parent) {
            $singular_name = $this->parent->getSingularName();
            $this->parent->addIdPattern($route, $singular_name);
        }
        $route->specify($name ? $name . '_id' : 'id', $this->id_pattern);
    }

    private function build()
    {
        if (!empty($this->routes)) {
            return;
        }
        $path = $this->getSingularBasePath() . '/:id';
        $singular_name = $this->getNamePrefix() . $this->getSingularName();
        $has_named_action_route = false;

        $options = array(
            'controller' => $this->controller
        );
        foreach ($this->member_actions as $action => $method) {
            $options['action'] = $action;
            switch ($action) {
                case 'show':
                case 'update':
                case 'destroy':
                    if (!$has_named_action_route) {
                        $route = new Route($path, $singular_name, $method, $options);
                        $has_named_action_route = true;
                    } else {
                        $route = new Route($path, NULL, $method, $options);
                    }
                    break;
                default:
                    $route_name = $action . '_' . $singular_name;
                    $route_path = $path . '/' . $action;
                    $route = new Route($route_path, $route_name, $method, $options);
            }
            $this->routes[] = $route;
        }

        $name = $this->getNamePrefix() . $this->getName();
        $path = $this->getBasePath();
        $has_named_action_route = false;
        foreach ($this->collection_actions as $action => $method) {
            $options['action'] = $action;
            switch ($action) {
                case 'index':
                case 'create':
                case 'update':
                case 'destroy':
                case 'show':
                    if (!$has_named_action_route) {
                        $route = new Route($path, $name, $method, $options);
                        $has_named_action_route = true;
                    } else {
                        $route = new Route($path, NULL, $method, $options);
                    }
                    break;
                default:
                    $route_name = $action . '_' . $singular_name;
                    $route_path = $path . '/' . $action;
                    $route = new Route($route_path, $route_name, $method, $options);
            }
            $this->routes[] = $route;
        }

        foreach ($this->routes as $route) {
            if ($this->id_pattern) {
                $this->addIdPattern($route);
            }
        }
    }

    public function match($path, $method = NULL)
    {
        $this->build();
        foreach ($this->routes as $route) {
            if ($route->match($path, $method)) {
                return $route;
            }
        }
        foreach ($this->resources as $resource) {
            $ret = $resource->match($path, $method);
            if ($ret) {
                return $ret;
            }
        }
        return false;
    }

    public function generate($name, array $parameters = array())
    {
        $this->build();
        foreach ($this->routes as $route) {
            $ret = $route->generate($name, $parameters);
            if ($ret) {
                return $ret;
            }
        }
        foreach ($this->resources as $resource) {
            $ret = $resource->generate($name, $parameters);
            if ($ret) {
                return $ret;
            }
        }
    }

}