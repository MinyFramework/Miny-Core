<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\View;

use Miny\Application\Application;
use Miny\Event\Event;
use Miny\Event\EventHandler;

class ViewEvents extends EventHandler
{
    private $app;
    public $exception = 'layouts/exception';

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function displayExceptionPage(Event $event)
    {
        $view = $this->app->view->get($this->exception);
        $view->app = $this->app;
        $view->exception = $event->getParameter('exception');
        $event->setResponse($view->render());
    }

    public function filterRequestFormat(Event $event)
    {
        $request = $event->getParameter('request');
        if (isset($request->get['format'])) {
            $this->app->view->setFormat($request->get['format']);
        }
    }

}