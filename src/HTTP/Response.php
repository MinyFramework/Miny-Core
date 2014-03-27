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
    public static $statusCodes = array(
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
    /**
     * @var ResponseHeaders
     */
    private $headers;
    private $statusCode;
    private $content;

    public function __construct(ResponseHeaders $headers = null)
    {
        $this->headers    = $headers ? : new ResponseHeaders();
        $this->content    = '';
        $this->statusCode = 200;
    }

    public function redirect($url, $code = 301)
    {
        $this->headers->set('Location', $url);
        $this->setCode($code);
    }

    public function removeCookie($name)
    {
        $this->headers->removeCookie($name);
    }

    public function setCookie($name, $value)
    {
        $this->headers->setCookie($name, $value);
    }

    public function setCode($code)
    {
        if (!isset(self::$statusCodes[$code])) {
            throw new InvalidArgumentException('Invalid status code: ' . $code);
        }
        $this->statusCode = $code;
    }

    /**
     * @return ResponseHeaders
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function getCookies()
    {
        return $this->headers->getCookies();
    }

    public function clearContent()
    {
        $this->content = '';
    }

    public function getContent()
    {
        return $this->content;
    }

    public function addResponse(Response $response)
    {
        $this->addContent($response->getContent());
    }

    public function addContent($content)
    {
        $this->content .= $content;
    }

    public function isCode($code)
    {
        return $this->statusCode === $code;
    }

    public function getCode()
    {
        return $this->statusCode;
    }

    public function getStatus()
    {
        return self::$statusCodes[$this->statusCode];
    }

    public function __toString()
    {
        return $this->content;
    }

    public function send()
    {
        $this->headers->setRaw(sprintf('HTTP/1.1 %d: %s', $this->statusCode, $this->getStatus()));
        $this->headers->send();
        if (!$this->headers->has('location')) {
            echo $this;
        }
    }

    public function serialize()
    {
        return serialize(
            array(
                $this->headers,
                $this->content,
                $this->statusCode
            )
        );
    }

    public function unserialize($serialized)
    {
        list($this->headers, $this->content, $this->statusCode) = unserialize($serialized);
    }
}
