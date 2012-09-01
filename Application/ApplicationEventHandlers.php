<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Exception;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\Routing\Exceptions\PageNotFoundException;

class ApplicationEventHandlers
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function logException(Exception $e)
    {
        $this->app->log->write(sprintf("%s \n Trace: %s", $e->getMessage(), $e->getTraceAsString()), get_class($e));
    }

    public function logRequest(Request $request)
    {
        $this->app->log->write(sprintf('Request: [%s] %s Source: %s', $request->method, $request->path, $request->ip));
    }

    public function logResponse(Request $request, Response $response)
    {
        $status = $response->getStatus();
        $log = $this->app->log;
        $log->write(sprintf('Response for request [%s] %s', $request->method, $request->path, $status));
        $log->write('Response status: ' . $status);
        if ($status == 'OK') {
            $log->write('Response content type: ' . $response->content_type);
            $log->write('Response body length: ' . strlen($response->getContent()));
        }
    }

    public function displayExceptionPage(Exception $e)
    {
//        $view = $this->app->view->get($this->app['view:exception']);
        $view = $this->app->view_factory->get('view', $this->app['view:exception']);
        $view->app = $this->app;
        $view->exception = $e;
        return $view->render();
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
            $this->app['content_type'] = $content_types[$format];
        }
    }

    public function filterRoutes(Request $request)
    {
        $app = $this->app;
        $match = $app->router->match($request->path, $request->method);
        if (!$match) {
            throw new PageNotFoundException('Page not found: ' . $request->path);
        }
        $app->log->write(sprintf('Matched route %s', $match->getRoute()->getPath()));
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_GET);
        $request->get = $match->getParameters() + $_GET;
        if (isset($request->get['format'])) {
            $this->setResponseContentType($request->get['format']);
            $app['view:default_format'] = '.' . $request->get['format'];
        }
    }

    public function setContentType(Request $request, Response $response)
    {
        if (isset($this->app['content_type'])) {
            $response->content_type = $this->app['content_type'];
        }
    }

    public function filterStringToResponse($response)
    {
        echo $response;
        return $this->app->response;
    }

}