<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Handlers;

use Miny\Factory\Container;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\Log\Log;
use Miny\Router\Match;
use Miny\Router\Route;
use Miny\Router\RouteMatcher;

class ApplicationEventHandlers
{
    /**
     * @var Log
     */
    private $log;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var ParameterContainer
     */
    private $parameterContainer;

    public function __construct(Container $container, Log $log, ParameterContainer $parameters)
    {
        $this->container          = $container;
        $this->log                = $log;
        $this->parameterContainer = $parameters;
    }

    private function log($category, $message)
    {
        $args = array_slice(func_get_args(), 2);
        $this->log->write(Log::INFO, $category, $message, $args);
    }

    public function logRequest(Request $request)
    {
        $this->log(
            'Request',
            '[%s] %s Source: %s',
            $request->getMethod(),
            $request->getUrl(),
            $request->getIp()
        );
        $headers = $request->getHeaders();
        if ($headers->has('referer')) {
            $this->log('Request', 'Referer: %s', $headers->get('referer'));
        }
    }

    public function logResponse(Request $request, Response $response)
    {
        $this->log(
            'Response',
            'Response for request [%s] %s',
            $request->getMethod(),
            $request->getPath()
        );
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
        $router       = $this->container->get('\\Miny\\Router\\RouteMatcher');
        $getContainer = $request->get();
        if ($this->parameterContainer['router:short_urls']) {
            $path = $request->getPath();
        } else {
            $path = $getContainer->get('path', '/');
        }
        /** @var $match Match */
        $methodMap = array(
            'GET'    => Route::METHOD_GET,
            'POST'   => Route::METHOD_POST,
            'PUT'    => Route::METHOD_PUT,
            'DELETE' => Route::METHOD_DELETE
        );
        $method    = strtoupper($request->getMethod());
        $method    = isset($methodMap[$method]) ? $methodMap[$method] : Route::METHOD_ALL;
        $match     = $router->match($path, $method);
        if (!$match) {
            $this->log(
                'Routing',
                'Route was not found for path [%s] %s',
                $request->getMethod(),
                $path
            );
            $response = $this->container->get('\\Miny\\HTTP\\Response');
            $response->setCode(404);

            return $response;
        }
        $this->log('Routing', 'Matched route %s', $match->getRoute()->getPath());
        parse_str(parse_url($request->getUrl(), PHP_URL_QUERY), $get);
        if (!$request->isSubRequest()) {
            $getContainer->add($_GET);
        }
        $getContainer->add($get);
        $getContainer->add($match->getParameters());
    }

    public function setContentType(Request $request, Response $response)
    {
        if (!$request->get()->has('format')) {
            return;
        }
        $headers = $response->getHeaders();
        if ($headers->has('content-type')) {
            return;
        }
        $format       = $request->get()->get('format');
        $content_type = $this->getResponseContentType($format);
        $content_type .= '; charset=' . $this->parameterContainer['default_content_encoding'];
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
