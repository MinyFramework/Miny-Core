<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Handlers;

use Exception;
use Miny\CoreEvents;
use Miny\Event\Event;
use Miny\Factory\Container;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\Log\Log;
use Miny\Router\RouteMatcher;
use Miny\Routing\Match;

class ApplicationEventHandlers
{
    /**
     * @var Log
     */
    protected $log;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var ParameterContainer
     */
    private $parameterContainer;

    public function __construct(Container $container, Log $log, ParameterContainer $parameterContainer)
    {
        $this->container = $container;
        $this->log       = $log;
        $this->parameterContainer = $parameterContainer;

        set_exception_handler(array($this, 'handleExceptions'));
    }

    public function handleExceptions(Exception $e)
    {
        /** @var $event Event */
        $event = $this->container->get('\\Miny\\Event\\EventDispatcher')
            ->raiseEvent(CoreEvents::UNCAUGHT_EXCEPTION, $e);
        if (!$event->isHandled()) {
            // Rethrow the exception that we did not handle.
            throw $e;
        }
        $response = $this->container->get('\\Miny\\HTTP\\Response');
        $response->addContent($event->getResponse());
        $response->setCode(500);
        $response->send();
    }

    private function log($category, $message)
    {
        $args = array_slice(func_get_args(), 2);
        $this->log->write(Log::INFO, $category, $message, $args);
    }

    public function logRequest(Request $request)
    {
        $this->log('Request', '[%s] %s Source: %s', $request->method, $request->url, $request->ip);
        $headers = $request->getHeaders();
        if ($headers->has('referer')) {
            $this->log('Request', 'Referer: %s', $headers->get('referer'));
        }
    }

    public function logResponse(Request $request, Response $response)
    {
        $this->log('Response', 'Response for request [%s] %s', $request->method, $request->path);
        $this->log(
            'Response',
            'Response status: %s %s',
            $response->getCode(),
            $response->getStatus()
        );
        foreach ($response->getHeaders() as $header => $value) {
            $this->log('Response', 'Header: %s: %s', ucfirst($header), $value);
        }
    }

    public function filterRoutes(Request $request)
    {
        /** @var $router RouteMatcher */
        $router = $this->container->get('\\Miny\\Router\\RouteMatcher');
        if ($this->parameterContainer['router:short_urls']) {
            $path = $request->path;
        } else {
            $path = $request->get('path', '/');
        }
        /** @var $match Match */
        $match = $router->match($path, $request->method);
        if (!$match) {
            $this->log('Routing', 'Route was not found for path [%s] %s', $request->method, $path);
            $response = $this->container->get('\\Miny\\HTTP\\Response');
            $response->setCode(404);

            return $response;
        }
        $this->log('Routing', 'Matched route %s', $match->getRoute()->getPath());
        parse_str(parse_url($request->url, PHP_URL_QUERY), $_GET);
        $request->get = $match->getParameters() + $_GET;
    }

    public function setContentType(Request $request, Response $response)
    {
        if (!isset($request->get['format'])) {
            return;
        }
        $headers = $response->getHeaders();
        if ($headers->has('content-type')) {
            return;
        }
        $format       = $request->get['format'];
        $content_type = $this->getResponseContentType($format);
        $headers->set('content-type', $content_type);
    }

    private function getResponseContentType($format)
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
            return $content_types[$format];
        }

        return $format;
    }
}
