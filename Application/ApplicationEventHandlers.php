<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Event\Event;
use Miny\Event\EventHandler;
use Miny\Routing\Exceptions\PageNotFoundException;

class ApplicationEventHandlers extends EventHandler
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Event $event)
    {
        $e = $event->getParameter('exception');
        $this->app->log->write(sprintf("%s \n Trace: %s", $e->getMessage(), $e->getTraceAsString()), get_class($e));
    }

    public function displayExceptionPage(Event $event)
    {
        $view = $this->app->view->get($this->app['view:exception']);
        $view->app = $this->app;
        $view->exception = $event->getParameter('exception');
        $event->setResponse($view->render());
    }

    public function handleRequestException(Event $event)
    {
        $request = $event->getParameter('request');
        if ($request->isSubRequest()) {
            return;
        }
        $ex = $event->getParameter('exception');
        $class = get_class($ex);
        if (!isset($this->app['router:exception_paths'][$class])) {
            throw $ex;
        }
        $path = $this->app['router:exception_paths'][$class];
        $this->app->log->write(sprintf('Redirect exception %s to %s', $class, $path));
        $request->path = $path;
    }

    private function setResponseContentType($format)
    {
        $content_types = array(
            //application
            'atom'  => 'application/atom+xml',
            'xhtml' => 'application/xhtml+xml',
            'rdf'   => 'application/rdf+xml',
            'rss'   => 'application/rss+xml',
            'json'  => 'application/json',
            'js'    => 'application/javascript',
            'tar'   => 'application/x-tar',
            'pdf'   => 'application/pdf',
            'ogg'   => 'application/ogg',
            //images
            'gif'   => 'image/gif',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'png'   => 'image/png',
            'svg'   => 'image/svg+xml',
            'tiff'  => 'image/tiff',
            'ico'   => 'image/vnd.microsoft.icon',
            //text
            'txt'   => 'text/plain',
            'xml'   => 'text/xml',
            'css'   => 'text/css',
            'csv'   => 'text/csv',
            'html'  => 'text/html',
        );
        if (isset($content_types[$format])) {
            $this->app->log->write(sprintf('Content type %s set for format %s', $content_types[$format], $format));
            $this->app->request->content_type = $content_types[$format];
        }
    }

    public function filterRoutes(Event $event)
    {
        $request = $event->getParameter('request');
        $match = $this->app->router->match($request->path, $request->method);
        if (!$match) {
            throw new PageNotFoundException('Page not found: ' . $request->path);
        }
        $this->app->log->write(sprintf('Matched route %s', $match->getRoute()->getPath()));
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_GET);
        $request->get = $match->getParameters() + $_GET;
        if (isset($request->get['format'])) {
            $this->setResponseContentType($request->get['format']);
            $this->app->view->setFormat($request->get['format']);
        }
    }

    public function filterStringToResponse(Event $event)
    {
        echo $event->getParameter('response');
        $event->setResponse($this->app->response);
    }

}