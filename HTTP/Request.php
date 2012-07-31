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
 * @version   1.0-dev
 */

namespace Miny\HTTP;

use InvalidArgumentException;

class Request
{
    const MASTER_REQUEST = 0;
    const SUB_REQUEST = 1;

    private static $request;

    public static function getGlobal()
    {
        if (is_null(self::$request)) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            self::$request = new Request($path, $_GET, $_POST, $_COOKIE);
        }
        return self::$request;
    }

    public $path;
    public $get;
    public $post;
    public $cookie;
    private $method;
    private $ip;
    private $type;

    public function __construct($path, array $get = array(), array $post = array(), array $cookie = array(),
                                $type = self::MASTER_REQUEST)
    {
        $this->path = $path;
        $this->get = $get;
        $this->post = $post;
        $this->cookie = $cookie;
        $this->type = $type;

        $this->ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

        if (!empty($this->post)) {
            $this->method = isset($this->post['_method']) ? $this->post['_method'] : 'POST';
        } else {
            $this->method = $_SERVER['REQUEST_METHOD'];
        }
    }

    public function __get($field)
    {
        if (!property_exists($this, $field)) {
            throw new InvalidArgumentException('Field not exists: ' . $field);
        }
        return $this->$field;
    }

    public function isSubRequest()
    {
        return $this->type == self::SUB_REQUEST;
    }

}