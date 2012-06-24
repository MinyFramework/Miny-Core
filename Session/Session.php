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
 * @package   Miny/Session
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Session;

class Session implements \ArrayAccess, \IteratorAggregate, \Countable {

    /**
     * Indicates whether a custom storage method is implemented.
     * @access private
     * @var boolean
     */
    private $custom_storage = false;

    /**
     * Indicates whether the session is started.
     * @access private
     * @var boolean
     */
    private $is_open = false;

    /**
     * Starts the session. Regenerates the session ID each request
     * for security reasons and updates flash variables.
     */
    public function open() {
        $this->registerCustomStorage();
        session_start();
        $this->is_open = true;
        session_regenerate_id(true);
        if (!isset($_SESSION['data'])) {
            $_SESSION = array(
                'data' => array(),
                'flash' => array()
            );
        }
        $this->updateFlash();
    }

    /**
     * Closes the current session.
     */
    public function close() {
        if ($this->is_open) {
            session_write_close();
            $this->is_open = false;
        }
    }

    /**
     * Destroys the current session and its data.
     */
    public function destroy() {
        if ($this->is_open) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * If a subclass implements session storage methods this method registers
     * them to be used as session handlers.
     * @access private
     */
    private function registerCustomStorage() {
        if ($this->custom_storage) {
            session_set_save_handler(
                    array($this, 'openSession'), array($this, 'closeSession'),
                    array($this, 'readSession'), array($this, 'writeSession'),
                    array($this, 'destroySession'), array($this, 'gcSession')
            );
            register_shutdown_function('session_write_close');
        }
    }

    /**
     * Updates time to live values for flash variable and removes old items.
     * @access private
     */
    private function updateFlash() {
        foreach ($_SESSION['flash'] as $key => &$array) {
            if ($array['set']-- == 0) {
                unset($_SESSION['flash'][$key]);
            }
        }
    }

    /**
     * Gets or sets a flash variable.
     *
     * A flash variable is a session variable that is only available
     * for a limited number of requests.
     *
     * @param string $key The key of the stored or accessed flashdata.
     * @param mixed $value The value to be stored
     * @param int $ttl The number of requests for the variable to be set.
     */
    public function flash($key, $value = NULL, $ttl = 1) {
        if (is_null($value)) {
            if (isset($_SESSION['flash'][$key])) {
                return $_SESSION['flash'][$key]['data'];
            }
        } else {
            $_SESSION['flash'][$key] = array('data' => $value, 'set'  => $ttl);
        }
    }

    /**
     * @return Wether a flashdata with $key key is set.
     * @param unknown_type $key
     */
    public function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }

    //Session handling methods

    public function openSession() {
        return true;
    }

    public function closeSession() {
        return true;
    }

    public function readSession($key) {
        return '';
    }

    public function writeSession($key, $value) {
        return true;
    }

    public function destroySession($key) {
        return true;
    }

    public function gcSession($lifetime) {
        return true;
    }

    //Session option methods

    /**
     * Gets the session ID.
     *
     * @return string the current session ID
     */
    public function sessionId() {
        return session_id();
    }

    /**
     * Sets or gets the session name.
     * @param string $value the session name for the current session
     *
     * @return string the current session name
     */
    public function sessionName($name = NULL) {
        if ($name !== NULL) {
            if (!$this->is_open) {
                session_name($name);
            }
        } else {
            return session_name();
        }
    }

    /**
     * Sets or gets the current session save path.
     *
     * @return string the current session save path.
     */
    public function savePath($path = NULL) {
        if (is_null($path)) {
            return session_save_path();
        } elseif (!$this->is_open) {
            if (!is_dir($path)) {
                throw new \InvalidArgumentException('Path not found: ' . $path);
            }
            session_save_path($path);
        }
    }

    /**
     * @see http://us2.php.net/manual/en/function.session-get-cookie-params.php
     */
    public function cookieParams(array $new_params = NULL) {
        $params = session_get_cookie_params();
        if (!is_null($new_params) && !$this->is_open) {
            $params = $new_params + $params;
            session_set_cookie_params(
                    $params['lifetime'], $params['path'], $params['domain'],
                    $params['secure'], $params['http_only']
            );
        }
        return $params;
    }

    //Session data methods

    public function keys() {
        return array_keys($_SESSION['data']);
    }

    public function toArray() {
        return $_SESSION['data'];
    }

    public function clean() {
        $_SESSION['data'] = array();
    }

    public function set($key, $value) {
        if (is_null($key)) {
            $_SESSION['data'][] = $value;
        } else {
            $_SESSION['data'][$key] = $value;
        }
    }

    public function has($key) {
        return isset($_SESSION['data'][$key]);
    }

    public function get($key, $default = NULL) {
        if (isset($_SESSION['data'][$key])) {
            return $_SESSION['data'][$key];
        }
        return $default;
    }

    public function remove($key) {
        unset($_SESSION['data'][$key]);
    }

    //Interfaces

    public function getIterator() {
        return new ArrayIterator($_SESSION['data']);
    }

    public function count() {
        return count($_SESSION['data']);
    }

    public function offsetSet($key, $value) {
        $this->set($key, $value);
    }

    public function offsetExists($key) {
        return $this->has($key);
    }

    public function offsetUnset($key) {
        $this->remove($key);
    }

    public function offsetGet($key) {
        return $this->get($key);
    }

}