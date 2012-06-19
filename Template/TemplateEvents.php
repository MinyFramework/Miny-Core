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

namespace Miny\Template;

use \Miny\Event\Event;

class TemplateEvents extends \Miny\Event\EventHandler {

    private $template_array = array();

    public function setTemplating($name, Template $t) {
        $this->template_array[$name] = $t;
    }

    public function handleException(Event $event) {
        $tpl = $this->template_array['layout'];
        $tpl->exception = $event->getParameter('exception');
        $event->setResponse($tpl->render('layouts/exception'));
    }

    public function filterRequest(Event $event) {
        $request = $event->getParameter('request');
        try {
            $format = $request->get('format');
            foreach ($this->template_array as $tpl) {
                $tpl->setFormat($format);
            }
        } catch (\OutOfBoundsException $e) {

        }
    }

    public function filterResponse(Event $event) {
        $request = $event->getParameter('request');
        if ($request->isSubRequest()) {
            return;
        }
        $rsp = $event->getParameter('response');
        $tpl = $this->template_array['layout'];

        $tpl->content = $rsp->getContent();
        $tpl->stylesheets = array('application', $request->get('controller'));

        $rsp->setContent($tpl->render('layouts/application'));
        $event->setResponse($rsp);
    }

}