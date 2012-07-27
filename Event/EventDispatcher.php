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
 * @version   1.0
 *
 */

namespace Miny\Event;

use \Miny\Log;

class EventDispatcher
{
    private $handlers = array();
    private $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }

    public function setHandler($event, EventHandler $handler, $method = NULL)
    {
        $this->handlers[$event][] = array($handler, $method);
    }

    public function raiseEvent(Event $event)
    {
        $name = $event->getName();
        if (isset($this->handlers[$name])) {
            $this->log->write(sprintf('Triggering event: %s Handlers: %d', $name, count($this->handlers[$name])));
            foreach ($this->handlers[$name] as $handler) {
                list($evt_handler, $method) = $handler;
                if (method_exists($evt_handler, $method)) {
                    $evt_handler->$method($event);
                } else {
                    $evt_handler->handle($event);
                }
            }
        } else {
            $this->log->write(sprintf('Triggering event: %s Handlers: %d', $name, 0));
        }
        $this->log->write(sprintf('Finished event: %s', $name));
    }

}