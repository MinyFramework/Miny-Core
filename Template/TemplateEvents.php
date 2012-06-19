<?php

namespace Miny\Template;

class TemplateEvents extends \Miny\Event\EventHandler {

    private $template_array = array();

    public function setTemplating($name, Template $t) {
        $this->template_array[$name] = $t;
    }

    public function filterRequest(\Miny\Event\Event $event) {
        $request = $event->getParameter('request');
        try {
            $format = $request->get('format');
            foreach ($this->template_array as $tpl) {
                $tpl->setFormat($format);
            }
        } catch (\OutOfBoundsException $e) {
            
        }
    }

    public function filterResponse(\Miny\Event\Event $event) {
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