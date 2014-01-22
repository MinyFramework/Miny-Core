<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use InvalidArgumentException;
use Miny\Utils\StringUtils;
use OutOfBoundsException;

class Request
{
    const MASTER_REQUEST = 0;
    const SUB_REQUEST    = 1;

    /**
     * @return Request
     */
    public static function getGlobal()
    {
        if (!empty($_POST)) {
            $method = isset($_POST['_method']) ? $_POST['_method'] : 'POST';
        } else {
            $method = $_SERVER['REQUEST_METHOD'];
        }

        $request         = new Request($method, $_SERVER['REQUEST_URI']);
        $request->type   = self::MASTER_REQUEST;
        $request->get    = $_GET;
        $request->post   = $_POST;
        $request->cookie = $_COOKIE;

        foreach ($_SERVER as $key => $value) {
            if (StringUtils::startsWith($key, 'HTTP_')) {
                $request->headers->set(substr($key, 5), $value);
            }
        }

        if ($request->headers->has('x-forwarded-for')) {
            $request->ip = $request->headers->get('x-forwarded-for');
        } else {
            $request->ip = $_SERVER['REMOTE_ADDR'];
        }

        return $request;
    }
    public $url;
    public $path;
    public $get;
    public $post;
    public $cookie;
    private $headers;
    private $method;
    private $ip;
    private $type;

    public function __construct($method, $url)
    {
        $this->url     = $url;
        $this->method  = strtoupper($method);
        $this->path    = parse_url($url, PHP_URL_PATH);
        $this->headers = new Headers();
    }

    public function __get($field)
    {
        if (!property_exists($this, $field)) {
            throw new OutOfBoundsException(sprintf('Field %s does not exist.', $field));
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

    /**
     * @return bool
     */
    public function isSubRequest()
    {
        return $this->type == self::SUB_REQUEST;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $post
     * @return Request
     */
    public function getSubRequest($method, $url, array $post = array())
    {
        $request = clone $this;

        $request->__construct($method, $url);
        $request->type = self::SUB_REQUEST;
        $request->post = $post;

        return $request;
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        if (!$this->headers->has('x-requested-with')) {
            return false;
        }
        return strtolower($this->headers->get('x-requested-with')) === 'xmlhttprequest';
    }

    /**
     * @return Headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
