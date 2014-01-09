<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use Iterator;
use OutOfBoundsException;
use Serializable;

/**
 * Headers is a simple container that contains and manages headers for HTTP Response objects.
 *
 * @author Dániel Buga
 */
class Headers implements Iterator, Serializable
{
    private static $multiple_values_allowed = array(
        'Accept',
        'Accept-Charset',
        'Accept-Encoding',
        'Accept-Language',
        'Accept-Ranges',
        'Allow',
        'Cache-Control',
        'Connection',
        'Content-Encoding',
        'Content-Language',
        'Expect',
        'If-Match',
        'If-None-Match',
        'Pragma',
        'Proxy-Authenticate',
        'TE',
        'Trailer',
        'Transfer-Encoding',
        'Upgrade',
        'Vary',
        'Via',
        'Warning',
        'WWW-Authenticate'
    );
    private $headers;
    private $raw_headers;

    public static function sanitize($name)
    {
        return strtolower(strtr($name, '_', '-'));
    }

    public function __construct()
    {
        $this->reset();
    }

    public function addHeaders(Headers $headers)
    {
        $this->headers = array_merge($this->headers, $headers->headers);
    }

    public function set($name, $value)
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->set($name, $item);
            }
            return;
        }
        $name = self::sanitize($name);
        if (isset($this->headers[$name])) {
            if (in_array($name, self::$multiple_values_allowed)) {
                if (!is_array($this->headers[$name])) {
                    $this->headers[$name] = array($this->headers[$name]);
                }
                $this->headers[$name][] = $value;
            } else {
                $this->headers[$name] = $value;
            }
        } else {
            $this->headers[$name] = $value;
        }
    }

    public function remove($name, $value = null)
    {
        $name = self::sanitize($name);
        if ($value === null) {
            unset($this->headers[$name]);
        } elseif (isset($this->headers[$name])) {
            if (is_array($this->headers[$name])) {
                if (($key = array_search($value, $this->headers[$name])) !== false) {
                    unset($this->headers[$name][$key]);
                }
            } elseif ($this->headers[$name] === $value) {
                unset($this->headers[$name]);
            }
        }
    }

    public function has($name)
    {
        $name = self::sanitize($name);
        return isset($this->headers[$name]);
    }

    public function reset()
    {
        $this->headers     = array();
        $this->raw_headers = array();
    }

    public function setRaw($header)
    {
        $this->raw_headers[] = $header;
    }

    public function get($name, $join = true)
    {
        $name = self::sanitize($name);
        if (!isset($this->headers[$name])) {
            throw new OutOfBoundsException(sprintf('%s header is not set.', $name));
        }
        if (is_array($this->headers[$name]) && $join) {
            return implode(', ', $this->headers[$name]);
        }
        return $this->headers[$name];
    }

    public function getRawHeaders()
    {
        return $this->raw_headers;
    }

    public function send()
    {
        foreach ($this as $header => $value) {
            header($header . ': ' . $value);
        }
        foreach ($this->raw_headers as $header) {
            header($header);
        }
    }

    public function __toString()
    {
        $return = '';
        foreach ($this as $header => $value) {
            $return .= $header . ': ' . $value . "\n";
        }
        foreach ($this->raw_headers as $header) {
            $return .= $header . "\n";
        }
        return $return;
    }

    public function current()
    {
        $item = current($this->headers);
        if (is_array($item)) {
            return implode(', ', $item);
        }
        return $item;
    }

    public function key()
    {
        return key($this->headers);
    }

    public function next()
    {
        next($this->headers);
    }

    public function rewind()
    {
        reset($this->headers);
    }

    public function valid()
    {
        return key($this->headers) !== null;
    }

    public function serialize()
    {
        return serialize(array(
            $this->headers,
            $this->raw_headers
        ));
    }

    public function unserialize($serialized)
    {
        $array             = unserialize($serialized);
        $this->headers     = array_shift($array);
        $this->raw_headers = array_shift($array);
    }
}
