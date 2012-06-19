<?php

namespace Miny\Routing;

class Route implements iRoute {

    private $name;
    private $path;
    private $default_parameters = array();
    private $matched_parameters = array();
    private $parameter_names = array();
    private $parameter_patterns = array();
    private $regex;
    private $static;

    public function __construct($path, $name = NULL, $method = NULL, array $default_parameters = array()) {
        $this->method = $method;
        $this->path = $path;
        $this->name = $name;
        $this->default_parameters = $default_parameters;
    }

    public function specify($parameter, $pattern) {
        $this->parameter_patterns[$parameter] = $pattern;
    }

    private function getParameterPattern($parameter) {
        if (isset($this->parameter_patterns[$parameter])) {
            return $this->parameter_patterns[$parameter];
        }
        return '(\w+)';
    }

    private function build() {
        if ($this->static !== NULL) {
            return;
        }
        $arr = array();
        if(!empty($this->path)){
            $path = $this->path . '.:format';
        } else {
            $path = '';
        }
        preg_match_all('/:(\w+)/', $path, $arr);
        $this->parameter_names = $arr[1];
        $tokens = array();
        foreach ($arr[1] as $k => $name) {
            $tokens[$arr[0][$k]] = $this->getParameterPattern($name);
        }
        $this->regex = str_replace(array('#', '?'), array('\#', '\?'), $path);
        $this->regex = str_replace(array_keys($tokens), $tokens, $this->regex);
    }

    public function match($path, $method = NULL) {
        if ($method !== NULL && $this->method !== NULL && $method !== $this->method) {
            return false;
        }
        $this->build();
        $matched = array();
        if (preg_match('#^' . $this->regex . '$#Du', $path, $matched)) {
            unset($matched[0]);
            foreach ($matched as $k => $v) {
                $this->matched_parameters[$this->parameter_names[$k - 1]] = $v;
            }
            return $this;
        }
        return false;
    }

    public function get($parameter = NULL) {
        if ($parameter === NULL) {
            return $this->default_parameters + $this->matched_parameters;
        }
        if (!isset($this->default_parameters[$parameter])) {
            if (!isset($this->matched_parameters[$parameter])) {
                throw new \OutOfBoundsException('Parameter not set: ' . $parameter);
            }
            return $this->matched_parameters[$parameter];
        }
        return $this->default_parameters[$parameter];
    }

    public function generate($name, array $parameters = array()) {
        if($this->name !== $name) {
            return false;
        }
        $this->build();
        foreach ($this->parameter_names as $param) {
            if (!array_key_exists($param, $parameters)) {
                throw new \InvalidArgumentException('Parameter not set: ' . $param);
            }
        }
        return $this->path . '.:format';
    }

}