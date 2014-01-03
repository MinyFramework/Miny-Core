<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Application\Application;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\Log;

class ApplicationEventHandlers
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Log
     */
    protected $log;

    public function __construct(Application $app, Log $log)
    {
        $this->app = $app;
        $this->log = $log;
    }

    public function logRequest(Request $request)
    {
        $this->log->info('Request: [%s] %s Source: %s', $request->method, $request->url, $request->ip);
        if (!empty($request->referer)) {
            $this->log->info('Request: [%s] Referer: %s', $request->method, $request->referer);
        }
    }

    public function logResponse(Request $request, Response $response)
    {
        $status = $response->getStatus();
        $log    = $this->log;
        $log->info('Response for request [%s] %s', $request->method, $request->path);
        $log->info('Response status: %s %s', $response->getCode(), $status);
        if ($status == 'OK') {
            $log->info('Response content type: %s', $response->content_type);
            $log->info('Response body length: %s', strlen($response->getContent()));
        }
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
            $this->log->info('Content type %s set for format %s', $content_types[$format], $format);
            $this->app['content_type'] = $content_types[$format];
        }
    }

    public function filterRoutes(Request $request)
    {
        if ($this->app->router->shortUrls()) {
            $path = $request->path;
        } else {
            $path = $request->get('path', '/');
        }
        $match = $this->app->router->match($path, $request->method);
        if (!$match) {
            $this->log->info('Route was not found for path [%s] %s', $request->method, $path);
            $response = new Response();
            $response->setCode(404);
            return $response;
        }
        $this->log->info('Matched route %s', $match->getRoute()->getPath());
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_GET);
        $request->get = $match->getParameters() + $_GET;
        if (isset($request->get['format'])) {
            $this->setResponseContentType($request->get['format']);
        }
    }

    public function setContentType(Request $request, Response $response)
    {
        if (isset($this->app['content_type'])) {
            $response->content_type = $this->app['content_type'];
        }
    }
}
