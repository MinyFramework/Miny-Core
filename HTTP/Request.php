<?php

namespace Miny\HTTP;

class Request {

    const MASTER_REQUEST = 0;
    const SUB_REQUEST = 1;

    private $params;
    private $type;

    public function __construct($path = NULL, $get = array(), $post = array(), $type = self::MASTER_REQUEST) {
        if ($path === NULL) {
            $this->getFromGlobals();
        } else {
            $this->params = array(
                'path' => $path,
                'get' => $get,
                'post' => $post
            );
        }
        if (!empty($this->params['post'])) {
            if (isset($this->params['post']['_method'])) {
                $method = $this->params['post']['_method'];
            } else {
                $method = 'POST';
            }
        } else {
            $method = $_SERVER['REQUEST_METHOD'];
        }
        $this->params['method'] = $method;
        $this->type = $type;
    }

    private function getFromGlobals() {
        $this->params = array(
            'path' => $_SERVER['QUERY_STRING'],
            'get' => $_GET,
            'post' => $_POST
        );
    }

    public function getRemoteIp() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    public function getHTTPParams() {
        return array_merge($this->params['get'], $this->params['post']);
    }

    public function get($key = NULL, $value = NULL) {
        return $this->param('get', $key, $value);
    }

    public function post($key = NULL, $value = NULL) {
        return $this->param('post', $key, $value);
    }

    private function param($type, $key, $value) {
        if (!$key && !$value) {
            return $this->params[$type];
        }
        if ($value) {
            if (is_array($value)) {
                $this->params[$type] = $value + $this->params[$type];
            } else {
                $this->params[$type][$key] = $value;
            }
        } else {
            if (!isset($this->params[$type][$key])) {
                throw new \OutOfBoundsException('Parameter not set: ' . $type . ' ' . $key);
            }
            return $this->params[$type][$key];
        }
    }

    public function __get($key) {
        if (!isset($this->params[$key])) {
            throw new \OutOfBoundsException('Parameter not set: ' . $key);
        }
        return $this->params[$key];
    }

    public function isSubRequest() {
        return $this->type == self::SUB_REQUEST;
    }

}