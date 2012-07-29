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
 * @package   Miny/Template
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\View;

use Miny\Event\Event;
use Miny\Event\EventHandler;

class ViewEvents extends EventHandler
{
    public $layout = 'layouts/application';
    public $exception = 'layouts/exception';
    public $formats = array('html');
    private $view;

    public function __construct(View $view, array $params = array())
    {
        $this->view = $view;
        $layout = $view->get('layout');
        foreach ($params as $k => $v) {
            $layout->$k = $v;
        }
    }

    public function handleException(Event $event)
    {
        $view = $this->view->get('layout');
        $view->file = $this->exception;
        $view->exception = $event->getParameter('exception');
        $event->setResponse($view->render());
    }

    public function filterRequestFormat(Event $event)
    {
        $request = $event->getParameter('request');
        if (isset($request->get['format'])) {
            $this->view->setFormat($request->get['format']);
        }
    }

    public function filterResponse(Event $event)
    {
        $request = $event->getParameter('request');
        if ($request->isSubRequest()) {
            return;
        }
        if (!empty($this->formats) && !in_array($request->get['format'], $this->formats)) {
            return;
        }
        $rsp = $event->getParameter('response');
        if ($rsp->isRedirect()) {
            return;
        }

        $view = $this->view->get('layout');
        $view->file = $this->layout;
        $view->content = $rsp->getContent();

        $rsp->setContent($view->render());
        $event->setResponse($rsp);
    }

}