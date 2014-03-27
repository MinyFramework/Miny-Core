<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

use Iterator;
use Miny\Utils\ArrayUtils;
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
        'accept',
        'accept-charset',
        'accept-encoding',
        'accept-language',
        'accept-ranges',
        'allow',
        'cache-control',
        'connection',
        'content-encoding',
        'content-language',
        'expect',
        'if-match',
        'if-none-match',
        'pragma',
        'proxy-authenticate',
        'te',
        'trailer',
        'transfer-encoding',
        'upgrade',
        'vary',
        'via',
        'warning',
        'www-authenticate'
    );
    private $headers;
    private $rawHeaders;

    public function __construct()
    {
        $this->reset();
    }

    public static function sanitize($name)
    {
        return strtolower(strtr($name, '_', '-'));
    }

    private function isHeaderSimple($name)
    {
        if (!isset($this->headers[$name])) {
            return true;
        }
        if (!in_array($name, self::$multiple_values_allowed)) {
            return true;
        }

        return false;
    }

    public function addHeaders(Headers $headers)
    {
        foreach ($headers->headers as $name => $value) {
            $this->set($name, $value);
        }
        $this->rawHeaders = array_merge($this->rawHeaders, $headers->rawHeaders);
    }

    public function set($name, $value)
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->setHeader($name, $item);
            }
        } else {
            $this->setHeader($name, $value);
        }
    }

    /**
     * @param $name
     * @param $value
     */
    protected function setHeader($name, $value)
    {
        $name = self::sanitize($name);
        if ($this->isHeaderSimple($name)) {
            $this->headers[$name] = $value;
        } else {
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = array($this->headers[$name]);
            }
            $this->headers[$name][] = $value;
        }
    }

    private function headerCanBeUnset($name, $value)
    {
        if ($value === null) {
            return true;
        }
        if ($this->headers[$name] === $value) {
            return true;
        }

        return false;
    }

    public function remove($name, $value = null)
    {
        $name = self::sanitize($name);
        if (!isset($this->headers[$name])) {
            return;
        }
        if ($this->headerCanBeUnset($name, $value)) {
            unset($this->headers[$name]);

            return;
        }
        if (!is_array($this->headers[$name])) {
            return;
        }
        if (($key = array_search($value, $this->headers[$name])) !== false) {
            unset($this->headers[$name][$key]);
            if (count($this->headers[$name]) === 1) {
                $this->headers[$name] = current($this->headers[$name]);
            }
        }
    }

    public function has($name, $value = null)
    {
        $name = self::sanitize($name);
        if (!isset($this->headers[$name])) {
            return false;
        }
        if (is_array($this->headers[$name])) {
            return in_array($value, $this->headers[$name]);
        }

        return $value === null || $this->headers[$name] === $value;
    }

    public function reset()
    {
        $this->headers    = array();
        $this->rawHeaders = array();
    }

    public function setRaw($header)
    {
        $this->rawHeaders[] = $header;
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
        return $this->rawHeaders;
    }

    public function __toString()
    {
        $return = '';
        foreach ($this as $header => $value) {
            $return .= $header . ': ' . $value . "\n";
        }
        foreach ($this->rawHeaders as $header) {
            $return .= $header . "\n";
        }

        return $return;
    }

    public function current()
    {
        return ArrayUtils::implodeIfArray(current($this->headers), ', ');
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
        return serialize(
            array(
                $this->headers,
                $this->rawHeaders
            )
        );
    }

    public function unserialize($serialized)
    {
        list($this->headers, $this->rawHeaders) = unserialize($serialized);
    }
}
