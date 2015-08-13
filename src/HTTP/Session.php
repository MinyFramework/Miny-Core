<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
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
     * @var FlashVariableStorage
     */
    private $flashStorage;

    /**
     * @param bool                     $open
     * @param \SessionHandlerInterface $handler
     */
    public function __construct($open = true, \SessionHandlerInterface $handler = null)
    {
        if ($handler) {
            session_set_save_handler($handler, true);
        }
        if ($open) {
            $this->open(null);
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
            $this->open(null);
        }
    }

    /**
     * Starts the session. Regenerates the session ID each request
     * for security reasons and updates flash variables.
     *
     * @param mixed $data The data to use as session data. Pass null to use the previous data, if any.
     *
     * @throws \RuntimeException When the session can not be opened.
     */
    public function open($data = null)
    {
        if (!session_start()) {
            throw new \RuntimeException('Could not open session.');
        }
        session_regenerate_id(true);
        if ($data === null) {
            $data =& $_SESSION;
        }

        $this->data         =& $data['data'];
        $this->flashStorage = new FlashVariableStorage($data['flash']);
        $this->flashStorage->decrement();

        $this->isOpen = true;
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
            if ($this->isOpen) {
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
        return $this->flashStorage->get($key);
    }

    public function __set($key, $data)
    {
        $this->flash($key, $data);
    }

    public function flash($key, $data, $ttl = 1)
    {
        $this->flashStorage->set($key, $data, $ttl);
    }

    public function __isset($key)
    {
        return $this->flashStorage->has($key);
    }

    public function __unset($key)
    {
        $this->flashStorage->remove($key);
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
        return new \ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function offsetSet($key, $value)
    {
        $this->data[ $key ] = $value;
    }

    public function offsetExists($key)
    {
        return isset($this->data[ $key ]);
    }

    public function offsetUnset($key)
    {
        unset($this->data[ $key ]);
    }

    public function &offsetGet($key)
    {
        if (!isset($this->data[ $key ])) {
            throw new \OutOfBoundsException("Session data '{$key}' is not found");
        }

        return $this->data[ $key ];
    }
}
