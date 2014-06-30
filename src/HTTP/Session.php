<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

class Session implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var bool
     */
    private $isOpen = false;

    /**
     * @param iSessionHandler $handler
     */
    public function __construct(iSessionHandler $handler = null)
    {
        if ($handler !== null) {
            if (PHP_MINOR_VERSION >= 4) {
                session_set_save_handler($handler, true);
            } else {
                session_set_save_handler(
                    array($handler, 'open'),
                    array($handler, 'close'),
                    array($handler, 'read'),
                    array($handler, 'write'),
                    array($handler, 'destroy'),
                    array($handler, 'gc')
                );
            }
        }
    }

    /**
     * Closes the current session.
     */
    public function close()
    {
        if ($this->isOpen) {
            session_write_close();
            $this->isOpen = false;
        }
    }

    /**
     * Destroys the current session and its data.
     */
    public function destroy($reopen = false)
    {
        if ($this->isOpen) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
            session_destroy();
            $this->isOpen = false;
        }
        if ($reopen) {
            $this->open();
        }
    }

    /**
     * Starts the session. Regenerates the session ID each request
     * for security reasons and updates flash variables.
     */
    public function open()
    {
        if (!session_start()) {
            throw new \RuntimeException('Could not open session.');
        }
        session_regenerate_id(true);
        $this->data =& $_SESSION;
        $this->initializeContainer('data');
        if (!$this->initializeContainer('flash')) {
            $this->updateFlash();
        }
        $this->isOpen = true;
    }

    private function initializeContainer($key)
    {
        if (!isset($this->data[$key]) || !is_array($this->data[$key])) {
            $this->data[$key] = array();

            return true;
        }

        return false;
    }

    /**
     * Updates time to live values for flash variable and removes old items.
     *
     * @access private
     */
    private function updateFlash()
    {
        $this->data['flash'] = array_filter(
            $this->data['flash'],
            function (&$flash) {
                // decrease flash ttl and remove if expired
                return $flash['ttl']-- > 0;
            }
        );
    }

    //Session option methods
    /**
     * Gets the session ID.
     *
     * @return string the current session ID
     */
    public function sessionId()
    {
        return session_id();
    }

    /**
     * Sets or gets the session name.
     *
     * @param string $name the session name for the current session
     *
     * @throws \InvalidArgumentException
     * @return string the current session name
     */
    public function sessionName($name = null)
    {
        if ($name === null) {
            return session_name();
        }
        if ($this->isOpen) {
            throw new \InvalidArgumentException('The session has already been opened.');
        }
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Session name must be null or a string');
        }
        session_name($name);
    }

    /**
     * Sets or gets the current session save path.
     *
     * @param string|null $path
     *
     * @throws \InvalidArgumentException
     * @return string the current session save path.
     */
    public function savePath($path = null)
    {
        if ($path === null) {
            return session_save_path();
        }
        if ($this->isOpen) {
            throw new \InvalidArgumentException('The session has already been opened.');
        }
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Session path must be null or a string');
        }
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Path not found: {$path}");
        }
        session_save_path($path);
    }

    /**
     * @see http://us2.php.net/manual/en/function.session-get-cookie-params.php
     */
    public function cookieParams(array $newParameters = null)
    {
        $params = session_get_cookie_params();
        if ($newParameters !== null) {
            if($this->isOpen) {
                throw new \InvalidArgumentException('The session has already been opened.');
            }
            $params = $newParameters + $params;
            session_set_cookie_params(
                $params['lifetime'],
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['http_only']
            );
        }

        return $params;
    }

    //Session data methods

    public function &__get($key)
    {
        if (!isset($this->data['flash'][$key])) {
            throw new \OutOfBoundsException("Flash key '{$key}' is not found");
        }

        return $this->data['flash'][$key]['data'];
    }

    public function __set($key, $data)
    {
        $this->flash($key, $data, 1);
    }

    public function flash($key, $data, $ttl)
    {
        $this->data['flash'][$key] = array('data' => $data, 'ttl' => (int)$ttl);
    }

    public function __isset($key)
    {
        return isset($this->data['flash'][$key]);
    }

    public function __unset($key)
    {
        unset($this->data['flash'][$key]);
    }

    public function __call($key, $arguments)
    {
        switch (count($arguments)) {
            case 0:
                throw new \BadMethodCallException('Flash calls must have at least one argument.');

            case 1:
                $arguments[1] = 1;
                break;
        }

        $this->flash($key, $arguments[0], $arguments[1]);
    }

    //Interfaces

    public function getIterator()
    {
        return new \ArrayIterator($this->data['data']);
    }

    public function count()
    {
        return count($this->data['data']);
    }

    public function offsetSet($key, $value)
    {
        $this->data['data'][$key] = $value;
    }

    public function offsetExists($key)
    {
        return isset($this->data['data'][$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->data['data'][$key]);
    }

    public function &offsetGet($key)
    {
        if (!isset($this->data['data'][$key])) {
            throw new \OutOfBoundsException("Session data '{$key}' is not found");
        }

        return $this->data['data'][$key];
    }
}
