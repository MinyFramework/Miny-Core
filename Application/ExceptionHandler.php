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
 * @package   Miny
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Application;

use \Miny\Log;
use \Miny\Event\Event;
use \Miny\Event\EventHandler;

class ExceptionHandler extends EventHandler
{
    private $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
    }

    public function handle(Event $event, $handling_method = NULL)
    {
        $e = $event->getParameter('exception');
        $this->log->write(sprintf("%s \n Trace: %s", $e->getMessage(), $e->getTraceAsString()), 'Exception');
    }

}