<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use InvalidArgumentException;

class Request
{
    const MASTER_REQUEST = 0;
    const SUB_REQUEST    = 1;

    private static $request;

    public static function getGlobal()
    {
        if (!isset(self::$request)) {
            self::$request = new Request($_SERVER['REQUEST_URI'], $_GET, $_POST, $_COOKIE);
        }
        return self::$request;
    }
    public $url;
    public $path;
    public $get;
    public $post;
    public $cookie;
    private $headers;
    private $method;
    private $ip;
    private $referer;
    private $type;

    public function __construct($url, array $get = array(), array $post = array(), array $cookie = array(),
                                $type = self::MASTER_REQUEST)
    {
        $this->url     = $url;
        $this->path    = parse_url($url, PHP_URL_PATH);
        $this->get     = $get;
        $this->post    = $post;
        $this->cookie  = $cookie;
        $this->type    = $type;
        $this->headers = new Headers();

        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->referer = $_SERVER['HTTP_REFERER'];
        }

        $this->ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

        if (!empty($this->post)) {
            $this->method = isset($this->post['_method']) ? $this->post['_method'] : 'POST';
        } else {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $this->headers->set(substr($key, 5), $value);
            }
        }
        if ($this->headers->has('x-forwarded-for')) {
            $this->headers->set('remote-addr', $this->headers->get('x-forwarded-for'));
        }
    }

    public function __get($field)
    {
        if (!property_exists($this, $field)) {
            throw new InvalidArgumentException('Field not exists: ' . $field);
        }
        return $this->$field;
    }

    public function __call($container, $args)
    {
        $key       = array_shift($args);
        $container = $this->__get($container);
        if (!is_string($key)) {
            throw new InvalidArgumentException('You need to supply a string key.');
        }
        if (isset($container[$key])) {
            return $container[$key];
        }
        if (count($args) > 0) {
            return current($args);
        }
        return null;
    }

    public function isSubRequest()
    {
        return $this->type == self::SUB_REQUEST;
    }

    public function getPreferredLanguage()
    {

    }

    public function isMethod($method)
    {

    }

    public function isAjax()
    {

    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
