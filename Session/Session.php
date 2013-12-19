<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Session;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;

class Session implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @var bool
     */
    private $is_open = false;

    /**
     * @param bool $custom_storage
     */
    public function __construct($custom_storage = false)
    {
        if ($custom_storage) {
            session_set_save_handler(
                    array($this, 'openSession'), array($this, 'closeSession'), array($this, 'readSession'),
                    array($this, 'writeSession'), array($this, 'destroySession'), array($this, 'gcSession')
            );
        }
    }

    /**
     * Starts the session. Regenerates the session ID each request
     * for security reasons and updates flash variables.
     */
    public function open()
    {
        $this->is_open = session_start();
        session_regenerate_id(true);

        if (!isset($_SESSION['data']) || !is_array($_SESSION['data'])) {
            $_SESSION['data'] = array();
        }
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            $_SESSION['flash'] = array();
        }
        $this->updateFlash();
    }

    /**
     * Closes the current session.
     */
    public function close()
    {
        if ($this->is_open) {
            session_write_close();
            $this->is_open = false;
        }
    }

    /**
     * Destroys the current session and its data.
     */
    public function destroy($reopen = true)
    {
        if ($this->is_open) {
            session_unset();
            session_destroy();
            $this->is_open = false;
        }
        if ($reopen) {
            $this->open();
        }
    }

    /**
     * Updates time to live values for flash variable and removes old items.
     * @access private
     */
    private function updateFlash()
    {
        foreach (array_keys($_SESSION['flash']) as $key) {
            if ($_SESSION['flash'][$key]['ttl'] -- == 0) {
                unset($_SESSION['flash'][$key]);
            }
        }
    }

    //Session handling methods

    public function openSession()
    {
        return true;
    }

    public function closeSession()
    {
        return true;
    }

    public function readSession($key)
    {
        return '';
    }

    public function writeSession($key, $value)
    {
        return true;
    }

    public function destroySession($key)
    {
        return true;
    }

    public function gcSession($lifetime)
    {
        return true;
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
     * @param string $value the session name for the current session
     *
     * @return string the current session name
     */
    public function sessionName($name = null)
    {
        if (!$name) {
            return session_name();
        }
        if (!is_string($name)) {
            throw new InvalidArgumentException('Session name must be null or a string');
        }
        if (!$this->is_open) {
            session_name($name);
        }
    }

    /**
     * Sets or gets the current session save path.
     *
     * @return string the current session save path.
     */
    public function savePath($path = null)
    {
        if (!$path) {
            return session_save_path();
        }
        if ($this->is_open) {
            return;
        }
        if (!is_string($path)) {
            throw new InvalidArgumentException('Session path must be null or a string');
        }
        if (!is_dir($path)) {
            throw new InvalidArgumentException('Path not found: ' . $path);
        }
        session_save_path($path);
    }

    /**
     * @see http://us2.php.net/manual/en/function.session-get-cookie-params.php
     */
    public function cookieParams(array $new_params = null)
    {
        $params = session_get_cookie_params();
        if (!is_null($new_params) && !$this->is_open) {
            $params = $new_params + $params;
            session_set_cookie_params(
                    $params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['http_only']
            );
        }
        return $params;
    }

    //Session data methods

    public function __set($key, $data)
    {
        $_SESSION['flash'][$key] = array('data' => $data, 'ttl' => 1);
    }

    public function &__get($key)
    {
        if (!isset($_SESSION['flash'][$key])) {
            throw new OutOfBoundsException('Session flash key not set: ' . $key);
        }
        return $_SESSION['flash'][$key]['data'];
    }

    public function __isset($key)
    {
        return isset($_SESSION['flash'][$key]);
    }

    public function __unset($key)
    {
        unset($_SESSION['flash'][$key]);
    }

    public function __call($key, $arguments)
    {
        $count = count($arguments);
        switch ($count) {
            case 0:
                throw new BadMethodCallException('Method must have at least one argument.');
            case 1:
                $arguments[1] = 1;
                break;
            default:
                if (!is_numeric($arguments[1])) {
                    throw new InvalidArgumentException('Secound argument must be a number.');
                }
        }

        $_SESSION['flash'][$key] = array('data' => $arguments[0], 'ttl' => $arguments[1]);
    }

    //Interfaces

    public function getIterator()
    {
        return new ArrayIterator($_SESSION['data']);
    }

    public function count()
    {
        return count($_SESSION['data']);
    }

    public function offsetSet($key, $value)
    {
        if ($key === null) {
            $_SESSION['data'][] = $value;
        } else {
            $_SESSION['data'][$key] = $value;
        }
    }

    public function offsetExists($key)
    {
        return isset($_SESSION['data'][$key]);
    }

    public function offsetUnset($key)
    {
        unset($_SESSION['data'][$key]);
    }

    public function &offsetGet($key)
    {
        if (!isset($_SESSION['data'][$key])) {
            throw new OutOfBoundsException('Session key not set: ' . $key);
        }
        return $_SESSION['data'][$key];
    }
}
