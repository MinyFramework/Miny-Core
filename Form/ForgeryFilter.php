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
 * @package   Miny/Form
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Form;

use \Miny\Event\Event;
use \Miny\Event\EventHandler;
use \Miny\Session\Session;

class ForgeryFilter extends EventHandler
{
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function filterRequest(Event $event)
    {
        $request = $event->getParameter('request');
        if ($request->isSubRequest()) {
            return;
        }

        $valid_tokens = $this->session->flash('tokens');
        $this->session->flash('tokens', array());

        if ($request->method == 'GET') {
            return;
        }
        $token = $request->post('token');
        if (!in_array($token, $valid_tokens)) {
            throw new \HttpRequestException('Wrong token sent.');
        }
    }

}