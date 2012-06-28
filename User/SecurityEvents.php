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
 * @package   Miny/User
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\User;

use \Miny\Event\Event;
use \Miny\Event\EventHandler;
use \Miny\Session\Session;

class SecurityEvents extends EventHandler
{
    private $security_provider;
    private $user_provider;
    private $identity;
    private $authenticated = false;

    public function setSecurityProvider(SecurityProvider $provider)
    {
        $this->security_provider = $provider;
    }

    public function setUserProvider(UserProvider $user_provider)
    {
        $this->user_provider = $user_provider;
    }

    public function authenticate(Session $session)
    {
        if ($this->authenticated) {
            return;
        }
        $user_provider = $this->user_provider;
        $user_set = isset($session['user']);
        if ($user_set && $user_provider->userExists($session['user'])) {
            $this->identity = $user_provider->getUser($session['user']);
        } else {
            $this->identity = $user_provider->getAnonymUser();
        }
        $session->flash('identity', $this->identity, 0);
        $this->authenticated = true;
    }

    private function hasAccess($rule)
    {
        if (is_string($rule) && !$this->identity->hasPermission($rule)) {
            return false;
        }
        if (is_callable($rule) && !call_user_func($rule)) {
            return false;
        }
        return true;
    }

    public function authorize(Event $event)
    {
        if (!$this->authenticated) {
            return;
        }
        if (is_null($this->security_provider)) {
            return;
        }

        $request = $event->getParameter('request');
        $provider = $this->security_provider;

        $controller = $request->get('controller');
        $action = $request->get('action');
        if ($provider->isActionProtected($controller, $action)) {
            $rules = $provider->getPermission($controller, $action);
            if (is_callable($rules) || is_string($rules)) {
                $rules = array($rules);
            }
            foreach ($rules as $rule) {
                if (!$this->hasAccess($rule)) {
                    $message = 'Access denied for path: ' . $request->path;
                    throw new \HttpRequestException($message);
                }
            }
        }
    }

}