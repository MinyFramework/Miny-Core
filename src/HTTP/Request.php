<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use Miny\Utils\ArrayReferenceWrapper;

class Request
{
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

        self::getUploadedFileInfo();

        $request = new Request(
            $method,
            $_SERVER['REQUEST_URI'],
            new ArrayReferenceWrapper($_GET),
            new ArrayReferenceWrapper($_POST),
            new ArrayReferenceWrapper($_COOKIE)
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

    private static function getUploadedFileInfo()
    {
        foreach ($_FILES as $field => $data) {
            if (is_array($data['error'])) {
                $files = [];
                foreach ($data['error'] as $fileKey => $error) {
                    $files[$fileKey] = new UploadedFileInfo(
                        $data['tmp_name'][$fileKey],
                        $data['name'][$fileKey],
                        $data['type'][$fileKey],
                        $data['size'][$fileKey],
                        $error
                    );
                }
                $_POST[$field] = $files;
            } else {
                $_POST[$field] = new UploadedFileInfo(
                    $data['tmp_name'],
                    $data['name'],
                    $data['type'],
                    $data['size'],
                    $data['error']
                );
            }
        }
    }

    private $url;
    private $path;
    private $get;
    private $post;
    private $cookie;
    private $headers;
    private $method;
    private $ip;
    private $isSubRequest = false;

    public function __construct($method, $url, $get = [], $post = [], $cookie = [])
    {
        $this->url     = $url;
        $this->method  = strtoupper($method);
        $this->path    = parse_url($url, PHP_URL_PATH);
        $this->headers = new Headers();
        $this->get     = new ParameterContainer($get);
        $this->post    = new ParameterContainer($post);
        $this->cookie  = new ParameterContainer($cookie);
    }

    /**
     * @return bool
     */
    public function isSubRequest()
    {
        return $this->isSubRequest;
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
        $request = new Request($method, $url,
            $this->get->toArray(),
            $post ? : $this->post->toArray(),
            $this->cookie->toArray()
        );

        $request->isSubRequest = true;

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
