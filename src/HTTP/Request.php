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

    private $url;
    private $path;
    private $get;
    private $post;
    private $cookie;
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
     * @param array  $post
     *
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

    public function get($key, $default = null)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('You need to supply a string key.');
        }
        if (isset($this->get[$key])) {
            return $this->get[$key];
        }

        return $default;
    }

    public function post($key, $default = null)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('You need to supply a string key.');
        }
        if (isset($this->post[$key])) {
            return $this->post[$key];
        }

        return $default;
    }

    public function cookie($key, $default = null)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('You need to supply a string key.');
        }
        if (isset($this->cookie[$key])) {
            return $this->cookie[$key];
        }

        return $default;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return Headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param array $get
     */
    public function setGetParameters(array $get)
    {
        $this->get = $get;
    }

    public function hasGetParameter($key)
    {
        return isset($this->get[$key]);
    }

    public function hasPostParameter($key)
    {
        return isset($this->post[$key]);
    }

    public function hasCookie($key)
    {
        return isset($this->cookie[$key]);
    }
}
