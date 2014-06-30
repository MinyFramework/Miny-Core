<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

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

        $request = new Request(
            $method,
            $_SERVER['REQUEST_URI'],
            new ReferenceParameterContainer($_GET),
            new ReferenceParameterContainer($_POST),
            new ReferenceParameterContainer($_COOKIE)
        );

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
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
    private $type = self::MASTER_REQUEST;

    public function __construct(
        $method,
        $url,
        ParameterContainer $get = null,
        ParameterContainer $post = null,
        ParameterContainer $cookie = null
    ) {
        $this->url     = $url;
        $this->method  = strtoupper($method);
        $this->path    = parse_url($url, PHP_URL_PATH);
        $this->headers = new Headers();
        $this->get     = $get ? : new ParameterContainer();
        $this->post    = $post ? : new ParameterContainer();
        $this->cookie  = $cookie ? : new ParameterContainer();
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
    public function getSubRequest($method, $url, array $post = null)
    {
        $request = clone $this;

        $request->__construct($method, $url);
        $request->type = self::SUB_REQUEST;
        if ($post === null) {
            $post = $this->post;
        } else {
            $post = new ParameterContainer($post);
        }
        $request->post = $post;

        return $request;
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->headers->has('x-requested-with', 'XMLHttpRequest');
    }

    /**
     * @return ParameterContainer
     */
    public function get()
    {
        return $this->get;
    }

    /**
     * @return ParameterContainer
     */
    public function post()
    {
        return $this->post;
    }

    /**
     * @return ParameterContainer
     */
    public function cookie()
    {
        return $this->cookie;
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
}
