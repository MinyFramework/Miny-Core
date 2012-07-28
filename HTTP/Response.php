<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/HTTP
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\HTTP;

class Response
{
    public static $status_codes = array(
        100      => 'Continue',
        101      => 'Switching Protocols',
        200      => 'OK',
        201      => 'Created',
        202      => 'Accepted',
        203      => 'Non-Authoritative Information',
        204      => 'No Content',
        205      => 'Reset Content',
        206      => 'Partial Content',
        300      => 'Multiple Choices',
        301      => 'Moved Permanently',
        302      => 'Found',
        303      => 'See Other',
        304      => 'Not Modified',
        305      => 'Use Proxy',
        306      => 'Temporary Redirect',
        400      => 'Bad Request',
        401      => 'Unauthorized',
        402      => 'Payment Required',
        403      => 'Forbidden',
        404      => 'Not Found',
        405      => 'Method Not Allowed',
        406      => 'Not Acceptable',
        407      => 'Proxy Authentication Required',
        408      => 'Request Timeout',
        409      => 'Conflict',
        410      => 'Gone',
        411      => 'Length Required',
        412      => 'Precondition Failed',
        413      => 'Request Entity Too Large',
        414      => 'Request-URI Too Long',
        415      => 'Unsupported Media Type',
        416      => 'Requested Range Not Satisfiable',
        417      => 'Expectation Failed',
        500      => 'Internal Server Error',
        501      => 'Not Implemented',
        502      => 'Bad Gateway',
        503      => 'Service Unavailable',
        504      => 'Gateway Timeout',
        505      => 'HTTP Version Not Supported'
    );
    private $content;
    private $cookies = array();
    private $headers = array();
    private $status_code;
    private $is_redirect = false;

    public function __construct($content = '', $code = 200)
    {
        $this->setContent($content);
        $this->setCode($code);
    }

    public function redirect($url, $code = 301)
    {
        $this->is_redirect = true;
        $this->setHeader('Location', $url);
        $this->setCode($code);
    }

    public function setCookie($name, $value)
    {
        $this->cookies[$name] = $value;
    }

    public function setCode($code)
    {
        if (!isset(self::$status_codes[$code])) {
            throw new \InvalidArgumentException('Invalid status code: ' . $code);
        }
        $this->status_code = $code;
    }

    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    public function setHeader($name, $value, $replace = true)
    {
        if ($replace) {
            $this->headers[$name] = $value;
        } else {
            if (!isset($this->headers[$name])) {
                $this->headers[$name] = array();
            }
            $this->headers[$name][] = $value;
        }
    }

    public function removeHeader($name, $value = NULL)
    {
        if (is_null($value)) {
            unset($this->headers[$name]);
        } else {
            if (!isset($this->headers[$name])) {
                return;
            }
            if (!is_array($this->headers[$name])) {
                return;
            }
            if (!is_array($value)) {
                $value = array($value);
            }
            $this->headers[$name] = array_diff($this->headers[$name], $value);
        }
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getCookies()
    {
        return $this->cookies;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getStatus()
    {
        return self::$status_codes[$this->status_code];
    }

    private function sendHTTPStatus()
    {
        $header = sprintf('HTTP/1.1 %d: %s', $this->status_code, $this->getStatus());
        header($header, true, $this->status_code);
    }

    protected function sendHeaders()
    {
        $this->sendHTTPStatus();
        foreach ($this->headers as $name => $header) {
            if (is_string($header)) {
                header($name . ': ' . $header);
            } elseif (is_array($header)) {
                foreach ($header as $h) {
                    header($name . ': ' . $h, false);
                }
            }
        }
        foreach ($this->cookies as $name => $value) {
            setcookie($name, $value);
        }
    }

    public function send()
    {
        $this->sendHeaders();
        if (!$this->is_redirect) {
            echo $this->content;
        }
    }

}