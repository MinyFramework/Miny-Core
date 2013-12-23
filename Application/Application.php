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
use Miny\Routing\Resource;
use Miny\Routing\Resources;
use Miny\Routing\Route;
use Miny\Session\Session;
use UnexpectedValueException;

require_once __DIR__ . '/BaseApplication.php';

class Application extends BaseApplication
{

    protected function setDefaultParameters()
    {
        $this->getParameters()->addParameters(array(
            'router' => array(
                'prefix'          => '/',
                'suffix'          => '.:format',
                'defaults'        => array(
                    'format' => 'html'
                ),
                'exception_paths' => array(),
                'short_urls'      => false
            ),
        ));
    }

    private function registerEventHandlers()
    {
        $app = $this;

        set_exception_handler(function (Exception $e) use ($app) {
            $event = $app->events->raiseEvent('uncaught_exception', $e);
            if (!$event->isHandled()) {
                throw $e;
            } else {
                $response = new Response;
                echo $event->getResponse();
                $response->setCode(500);
                $response->send();
            }
        });

        $eh = new ApplicationEventHandlers($this, $this->log);
        $this->getBlueprint('events')
                ->addMethodCall('register', 'filter_request', array($eh, 'logRequest'))
                ->addMethodCall('register', 'filter_request', array($eh, 'filterRoutes'))
                ->addMethodCall('register', 'filter_response', array($eh, 'setContentType'))
                ->addMethodCall('register', 'filter_response', array($eh, 'logResponse'));
    }

    protected function registerDefaultServices()
    {
        parent::registerDefaultServices();
        $this->registerEventHandlers();

        $this->add('controllers', '\Miny\Controller\ControllerCollection')
                ->setArguments('&app');
        $this->add('resolver', '\Miny\Controller\ControllerResolver')
                ->setArguments('&controllers');
        $this->add('router', '\Miny\Routing\Router')
                ->setArguments('@router:prefix', '@router:suffix', '@router:defaults', '@router:short_urls');

        $session = new Session;
        $session->open();

        if (!isset($session['token'])) {
            $session['token'] = sha1(mt_rand());
        }

        $this->session = $session;
        $this->request = Request::getGlobal();
    }

    /**
     *
     * @param string $name
     * @param mixed $controller
     * @param array $parameters
     * @return Resource
     */
    public function resource($name, $controller = null, array $parameters = array())
    {
        $parameters['controller'] = $controller ? : $name;
        return $this->router->resource($name, $parameters);
    }

    /**
     *
     * @param string $name
     * @param mixed $controller
     * @param array $parameters
     * @return Resources
     */
    public function resources($name, $controller = null, array $parameters = array())
    {
        $parameters['controller'] = $controller ? : $name;
        return $this->router->resources($name, $parameters);
    }

    /**
     *
     * @param mixed $controller
     * @param array $parameters
     * @return Route
     */
    public function root($controller, array $parameters = array())
    {
        $controller_name          = is_string($controller) ? $controller : $this->controllers->getNextName();
        $parameters['controller'] = $controller_name;
        $this->controllers->register($controller_name, $controller);
        return $this->router->root($parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string|null $method
     * @param string|null $name
     * @param array $parameters
     * @return Route
     * @throws UnexpectedValueException
     */
    public function route($path, $controller, $method = null, $name = null, array $parameters = array())
    {
        $method                   = strtoupper($method);
        $controller_name          = is_string($controller) ? $controller : $this->controllers->getNextName();
        $parameters['controller'] = $controller_name;
        $this->controllers->register($controller_name, $controller);

        $route = new Route($path, $method, $parameters);
        return $this->router->route($route, $name);
    }

    public function __call($method, $args)
    {
        switch ($method) {
            case 'get':
            case 'post':
            case 'put':
            case 'delete':
                call_user_func_array(array($this, 'route'), $args);
                break;
            default:
                return parent::__call($method, $args);
        }
    }

    /**
     *
     */
    public function onRun()
    {
        date_default_timezone_set($this['default_timezone']);
        $this->dispatch($this->request)->send();
    }

    /**
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request)
    {
        $event = $this->events->raiseEvent('filter_request', $request);

        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                $response = $rsp;
            } elseif ($rsp instanceof Request && $rsp !== $request) {
                return $this->dispatch($rsp);
            }
        }

        if (!isset($response)) {
            $response = new Response;
            $action   = isset($request->get['action']) ? $request->get['action'] : null;
            $this->resolver->resolve($request->get['controller'], $action, $request, $response);
        }

        $this->events->raiseEvent('filter_response', $request, $response);
        return $response;
    }
}
