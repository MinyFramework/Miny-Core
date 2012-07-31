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
 * @package   Miny/Event
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0-dev
 */

namespace Miny\Event;

use InvalidArgumentException;

class Event
{
    private $parameters;
    private $name;
    private $response;
    private $is_handled = false;

    public function __construct($name, array $parameters = array())
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function isHandled()
    {
        return $this->is_handled;
    }

    public function setHandled()
    {
        $this->is_handled = true;
    }

    public function getName()
    {
        return $this->name;
    }

    public function hasParameter($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    public function getParameter($key)
    {
        if (!$this->hasParameter($key)) {
            $message = 'Event parameter not set: ' . $key;
            throw new InvalidArgumentException($message);
        }
        return $this->parameters[$key];
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }

    public function hasResponse()
    {
        return $this->response !== NULL;
    }

    public function getResponse()
    {
        return $this->response;
    }

}