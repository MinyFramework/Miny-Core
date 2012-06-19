<?php

namespace Miny\Routing;

class Router {

    private $routes = array();
    private $url_prefix;
    private $format;

    public function __construct($format = 'html', $prefix = NULL){
        $this->url_prefix = $prefix;
        $this->format = $format;
    }

    public function resource($name){
        $resource = new ResourceRoute($name, NULL, true);
        $this->routes[$name] = $resource;
        return $resource;
    }

    public function resources($name){
        $resource = new ResourceRoute($name);
        $this->routes[$name] = $resource;
        return $resource;
    }

    public function get($path, $name = NULL, array $params = array()) {
        return $this->add('GET', $path, $name, $params);
    }

    public function post($path, $name = NULL, array $params = array()) {
        return $this->add('POST', $path, $name, $params);
    }

    public function put($path, $name = NULL, array $params = array()) {
        return $this->add('PUT', $path, $name, $params);
    }

    public function delete($path, $name = NULL, array $params = array()) {
        return $this->add('DELETE', $path, $name, $params);
    }

    public function add($method, $path, $name = NULL, array $params = array()) {
        $route = new Route($path, $name, $method, $params);
        $this->routes[$name] = $route;
        return $route;
    }

    public function matchCurrent() {
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_GET);
        if (isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
            if ($method != 'PUT' && $method != 'DELETE') {
                $method = $_SERVER['REQUEST_METHOD'];
            }
        } else {
            $method = $_SERVER['REQUEST_METHOD'];
        }
        if (($pos = strpos($_SERVER['QUERY_STRING'], '&')) !== false) {
            $query_string = substr($_SERVER['QUERY_STRING'], 0, $pos);
        } else {
            $query_string = $_SERVER['QUERY_STRING'];
        }
        return $this->match($query_string, $method);
    }

    public function match($path, $method = NULL) {
        foreach ($this->routes as $route) {
            $route = $route->match($path, $method);
            if ($route) {
                return $route;
            }
        }
    }

    public function generate($name, array $params = array()) {
        foreach ($this->routes as $route) {
            $path = $route->generate($name, $params);
            if(!isset($params['format'])) {
                $params['format'] = $this->format;
            }
            if($path){
                $first_http_param = true;
                if(!is_null($this->url_prefix)){
                    if(strpos($this->url_prefix, '?') !== false){
                        $first_http_param = false;
                    }
                    $path = $prefix . $path;
                }
                foreach ($params as $name => $value) {
                    if (strpos($path, ':'.$name) !== false) {
                        $path = str_replace(':' . $name, $value, $path);
                    } else {
                        if($first_http_param){
                            $path .= sprintf('?%s=%s', $name, $value);
                        } else {
                            $path .= sprintf('&%s=%s', $name, $value);
                        }
                    }
                }
                return $path;
            }
        }
    }

}