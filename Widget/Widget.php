<?php

namespace Miny\Widget;

abstract class Widget implements iWidget {

    private $assigns = array();
    private $services = array();
    protected $view;

    public function __set($key, $value) {
        $this->assigns[$key] = $value;
    }
    
    public function service($key, $service) {
        $this->services[$key] = $service;
    }

    public function __get($key) {
        if (isset($this->services[$key])) {
            return $this->services[$key];
        } else {
            if (array_key_exists($key, $this->assigns)) {
                return $this->assigns[$key];
            }
            throw new \OutOfBoundsException('Variable not set: ' . $key);
        }
    }

    public function begin(array $params = array()) {
        return $this;
    }

    public function end(array $params = array()) {
        $tpl = $this->templating;

        $this->run($params);

        foreach ($this->assigns as $key => $value) {
            $tpl->$key = $value;
        }

        if (is_null($this->view)) {
            throw new \RuntimeException('Template not set.');
        }

        $response = $tpl->render('widgets/' . $this->view);

        $tpl->clean();
        return $response;
    }

}