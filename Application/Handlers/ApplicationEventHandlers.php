<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Handlers;

use Exception;
use Miny\Factory\Factory;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\Log;

class ApplicationEventHandlers
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var ParameterContainer
     */
    private $parameters;

    /**
     * @var Log
     */
    protected $log;

    public function __construct(Factory $factory)
    {
        $this->factory    = $factory;
        $this->parameters = $factory->getParameters();
        $this->log        = $factory->log;

        set_exception_handler(array($this, 'handleExceptions'));
    }

    public function handleExceptions(Exception $e)
    {
        $event = $this->factory->events->raiseEvent('uncaught_exception', $e);
        if (!$event->isHandled()) {
            throw $e;
        } else {
            $response = $this->factory->response;
            $response->addContent($event->getResponse());
            $response->setCode(500);
            $response->send();
        }
    }

    public function logRequest(Request $request)
    {
        $this->log->info('Request: [%s] %s Source: %s', $request->method, $request->url, $request->ip);
        $headers = $request->getHeaders();
        if ($headers->has('referer')) {
            $this->log->info('Request: Referer: %s', $headers->get('referer'));
        }
    }

    public function logResponse(Request $request, Response $response)
    {
        $log = $this->log;
        $log->info('Response for request [%s] %s', $request->method, $request->path);
        $log->info('Response status: %s %s', $response->getCode(), $response->getStatus());
        foreach ($response->getHeaders() as $header => $value) {
            $log->info($header . ': ' . $value);
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
            //image
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
            $this->parameters['content_type'] = $content_types[$format];
        }
    }

    public function filterRoutes(Request $request)
    {
        if ($this->factory->router->shortUrls()) {
            $path = $request->path;
        } else {
            $path = $request->get('path', '/');
        }
        $match = $this->factory->router->match($path, $request->method);
        if (!$match) {
            $this->log->info('Route was not found for path [%s] %s', $request->method, $path);
            $response = $this->factory->response;
            $response->setCode(404);
            return $response;
        }
        $this->log->info('Matched route %s', $match->getRoute()->getPath());
        parse_str(parse_url($request->url, PHP_URL_QUERY), $_GET);
        $request->get = $match->getParameters() + $_GET;
        if (isset($request->get['format'])) {
            $this->setResponseContentType($request->get['format']);
        }
    }

    public function setContentType(Request $request, Response $response)
    {
        if (isset($this->parameters['content_type'])) {
            $response->getHeaders()->set('content-type', $this->parameters['content_type']);
        }
    }
}