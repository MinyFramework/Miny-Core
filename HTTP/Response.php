<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use InvalidArgumentException;
use Serializable;

class Response implements Serializable
{
    public static $status_codes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );
    private $cookies;
    private $headers;
    private $status_code        = 200;
    private $output_stack;

    public function __construct()
    {
        $this->headers      = new Headers();
        $this->cookies      = array();
        $this->output_stack = array();
        ob_start();
    }

    public function redirect($url, $code = 301)
    {
        $this->headers->set('Location', $url);
        $this->setCode($code);
    }

    public function setCookie($name, $value)
    {
        $this->cookies[$name] = $value;
    }

    public function setCode($code)
    {
        if (!isset(self::$status_codes[$code])) {
            throw new InvalidArgumentException('Invalid status code: ' . $code);
        }
        $this->status_code = $code;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getCookies()
    {
        return $this->cookies;
    }

    public function getContent($clean = false)
    {
        return $clean ? ob_get_clean() : ob_get_contents();
    }

    public function isCode($code)
    {
        return $this->status_code === $code;
    }

    public function getCode()
    {
        return $this->status_code;
    }

    public function getStatus()
    {
        return self::$status_codes[$this->status_code];
    }

    public function addResponse(Response $response)
    {
        $this->output_stack[] = $this->getContent(true);
        $this->output_stack[] = $response;
    }

    public function send()
    {
        $this->headers->setRaw(sprintf('HTTP/1.1 %d: %s', $this->status_code, $this->getStatus()));
        foreach ($this->cookies as $name => $value) {
            setcookie($name, $value);
        }
        $this->headers->send();
        if (!$this->headers->has('location')) {
            foreach ($this->output_stack as $output) {
                echo $output;
            }
            ob_end_flush();
        }
    }

    public function serialize()
    {
        return serialize(array(
            $this->content_type,
            $this->cookies,
            $this->headers,
            $this->status_code
        ));
    }

    public function unserialize($serialized)
    {
        $data               = unserialize($serialized);
        $this->content_type = array_shift($data);
        $this->cookies      = array_shift($data);
        $this->headers      = array_shift($data);
        $this->status_code  = array_shift($data);
        ob_start();
    }
}
