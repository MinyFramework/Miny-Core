<?php

namespace Miny\Routing;

class ResourceRoute {

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
    private $base_path;
    private $name_prefix;
    private $nice_url;

    public function __construct($name, ResourceRoute $parent = NULL, $singular = false) {
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
                'show' => 'GET',
                'destroy' => 'DELETE',
                'edit' => 'GET',
                'update' => 'PUT',
                'new' => 'GET',
                'create' => 'POST'
            );
        } else {
            $this->member_actions = array(
                'show' => 'GET',
                'destroy' => 'DELETE',
                'edit' => 'GET',
                'update' => 'PUT',
            );

            $this->collection_actions = array(
                'index' => 'GET',
                'new' => 'GET',
                'create' => 'POST'
            );
        }
    }

    public function niceUrl($nice = true) {
        $this->nice_url = $nice;
    }

    public function getName() {
        return $this->name;
    }

    public function getSingularName() {
        return $this->singular_name;
    }

    public function getPathAlias() {
        return $this->path_alias;
    }

    public function path($name) {
        $this->path_alias = $name;
        return $this;
    }

    public function specify($pattern) {
        $this->id_pattern = $pattern;
        return $this;
    }

    public function controller($name) {
        $this->controller = $name;
        return $this;
    }

    public function singularForm($name) {
        if (!$this->singular) {
            $this->singular_name = $name;
        }
        return $this;
    }

    public function only() {
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

    public function except() {
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

    public function member($method, $name) {
        $this->member_actions[$name] = $method;
        return $this;
    }

    public function collection($method, $name) {
        $this->collection_actions[$name] = $method;
        return $this;
    }

    public function resource($name) {
        $resource = new ResourceRoute($name, $this, true);
        $this->resources[$name] = $resource;
        return $resource;
    }

    public function resources($name) {
        $resource = new ResourceRoute($name, $this);
        $this->resources[$name] = $resource;
        return $resource;
    }

    public function getBasePath() {
        if (is_null($this->base_path)) {
            if ($this->parent) {
                $this->base_path = $this->parent->getBasePath() . ':' . $this->parent->getSingularName() . '_id/' . $this->path_alias;
            } else {
                $this->base_path = $this->path_alias;
            }
        }
        return $this->base_path;
    }

    public function getSingularBasePath() {
        if (is_null($this->base_path)) {
            if ($this->parent) {
                $this->singular_base_path = $this->parent->getBasePath() . ':' . $this->parent->getSingularName() . '_id/' . $this->singular_name;
            } else {
                $this->singular_base_path = $this->singular_name;
            }
        }
        return $this->singular_base_path;
    }

    public function getNamePrefix() {
        if (is_null($this->name_prefix)) {
            if ($this->parent) {
                $this->name_prefix = $this->parent->getNamePrefix() . '_' . $this->parent->getSingularName() . '_';
            } else {
                $this->name_prefix = '';
            }
        }
        return $this->name_prefix;
    }

    public function addIdPattern(Route $route, $name = NULL) {
        if ($this->parent) {
            $this->parent->addIdPattern($route, $this->parent->getSingularName());
        }
        $key = $name ? $name . '_id' : 'id';
        $route->specify($key, $this->id_pattern);
    }

    public function build() {
        if (!empty($this->routes))
            return;
        $member_path = $this->getSingularBasePath() . '/:id';
        $singular_name = $this->getNamePrefix() . $this->getSingularName();
        $has_named = false;
        foreach ($this->member_actions as $action => $method) {
            $options = array(
                'controller' => $this->controller,
                'action' => $action
            );
            switch ($action) {
                case 'show':
                case 'update':
                case 'destroy':
                    if (!$has_named) {
                        $route = new Route($member_path, $singular_name, $method, $options);
                        $has_named = true;
                    } else {
                        $route = new Route($member_path, NULL, $method);
                    }
                    break;
                default:
                    $route = new Route($member_path . '/' . $action, $action . '_' . $singular_name, $method);
            }
            $this->routes[] = $route;
        }

        $name = $this->getNamePrefix() . $this->getName();
        $path = $this->getBasePath();
        $has_named = false;
        foreach ($this->collection_actions as $action => $method) {
            $options = array(
                'controller' => $this->controller,
                'action' => $action
            );
            switch ($action) {
                case 'index':
                case 'create':
                case 'update':
                case 'destroy':
                case 'show':
                    if (!$has_named) {
                        $route = new Route($path, $name, $method, $options);
                        $has_named = true;
                    } else {
                        $route = new Route($path, NULL, $method, $options);
                    }
                    break;
                default:
                    $route = new Route($path . '/' . $action, $action . '_' . $singular_name, $method, $options);
            }
            $this->routes[] = $route;
        }

        foreach ($this->routes as $route) {
            if ($this->id_pattern) {
                $this->addIdPattern($route);
            }
        }
    }

    public function match($path, $method = NULL) {
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

    public function generate($name, array $parameters = array()) {
        $this->build();
        foreach ($this->routes as $route) {
            $ret = $route->generate($name, $parameters);
            if ($ret) {
                return $ret;
            }
        }
        foreach ($this->resources as $resource) {
            $ret = $resource->match($path, $method);
            if ($ret) {
                return $ret;
            }
        }
    }

}