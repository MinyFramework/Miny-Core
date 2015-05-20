<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use InvalidArgumentException;
use Serializable;

class Response implements Serializable
{
    const CONTINUE_ = 100;
    const SWITCHING_PROTOCOLS = 101;
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NON_AUTHORITATIVE_INFORMATION = 203;
    const NO_CONTENT = 204;
    const RESET_CONTENT = 205;
    const PARTIAL_CONTENT = 206;
    const MULTIPLE_CHOICES = 300;
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const SEE_OTHER = 303;
    const NOT_MODIFIED = 304;
    const USE_PROXY = 305;
    const SWITCH_PROXY = 306;
    const TEMPORARY_REDIRECT = 307;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const PAYMENT_REQUIRED = 402;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const NOT_ACCEPTIBLE = 406;
    const PROXY_AUTHENTICATION_REQUIRED = 407;
    const REQUEST_TIMEOUT = 408;
    const CONFLICT = 409;
    const GONE = 410;
    const LENGTH_REQUIRED = 411;
    const PRECONDITION_FAILED = 412;
    const REQUEST_ENTITY_TOO_LARGE = 413;
    const URI_TOO_LONG = 414;
    const UNSUPPORTED_MEDIA_TYPE = 415;
    const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const EXPECTATION_FAILED = 417;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;

    private static $statusCodes = [
        self::CONTINUE_ => 'Continue',
        self::SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::OK => 'OK',
        self::CREATED => 'Created',
        self::ACCEPTED => 'Accepted',
        self::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
        self::NO_CONTENT => 'No Content',
        self::RESET_CONTENT => 'Reset Content',
        self::PARTIAL_CONTENT => 'Partial Content',
        self::MULTIPLE_CHOICES => 'Multiple Choices',
        self::MOVED_PERMANENTLY => 'Moved Permanently',
        self::FOUND => 'Found',
        self::SEE_OTHER => 'See Other',
        self::NOT_MODIFIED => 'Not Modified',
        self::USE_PROXY => 'Use Proxy',
        self::SWITCH_PROXY => 'Switch Proxy',
        self::TEMPORARY_REDIRECT => 'Temporary Redirect',
        self::BAD_REQUEST => 'Bad Request',
        self::UNAUTHORIZED => 'Unauthorized',
        self::PAYMENT_REQUIRED => 'Payment Required',
        self::FORBIDDEN => 'Forbidden',
        self::NOT_FOUND => 'Not Found',
        self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::NOT_ACCEPTIBLE => 'Not Acceptable',
        self::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
        self::REQUEST_TIMEOUT => 'Request Timeout',
        self::CONFLICT => 'Conflict',
        self::GONE => 'Gone',
        self::LENGTH_REQUIRED => 'Length Required',
        self::PRECONDITION_FAILED => 'Precondition Failed',
        self::REQUEST_ENTITY_TOO_LARGE => 'Request Entity Too Large',
        self::URI_TOO_LONG => 'Request-URI Too Long',
        self::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
        self::REQUESTED_RANGE_NOT_SATISFIABLE => 'Requested Range Not Satisfiable',
        self::EXPECTATION_FAILED => 'Expectation Failed',
        self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::NOT_IMPLEMENTED => 'Not Implemented',
        self::BAD_GATEWAY => 'Bad Gateway',
        self::SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::GATEWAY_TIMEOUT => 'Gateway Timeout',
        self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported'
    ];
    /**
     * @var ResponseHeaders
     */
    private $headers;
    private $statusCode;
    private $content;

    public function __construct(ResponseHeaders $headers = null)
    {
        $this->headers    = $headers ?: new ResponseHeaders(new NativeHeaderSender());
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
            throw new InvalidArgumentException("Invalid status code: {$code}");
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
        $this->headers->setRaw("HTTP/1.1 {$this->statusCode}: {$this->getStatus()}");
        $this->headers->send();
        if (!$this->headers->has('location')) {
            echo $this;
        }
    }

    public function serialize()
    {
        return serialize(
            [
                $this->headers,
                $this->content,
                $this->statusCode
            ]
        );
    }

    public function unserialize($serialized)
    {
        list($this->headers, $this->content, $this->statusCode) = unserialize($serialized);
    }
}
