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
                'exception_paths' => array()
            ),
            'site'   => array(
                'title'    => 'Miny 1.0',
                'base_url' => 'http://' . $_SERVER['HTTP_HOST'] . '{@router:prefix}'
        )));
    }

    private function registerEventHandlers()
    {
        $app = $this;

        set_exception_handler(function(Exception $e) use($app) {
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
                ->setArguments('@router:prefix', '@router:suffix', '@router:defaults');

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
    public function resource($name, $controller = NULL, array $parameters = array())
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
    public function resources($name, $controller = NULL, array $parameters = array())
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
     * @param type $path
     * @param type $controller
     * @param type $method
     * @param type $name
     * @param array $parameters
     * @return Route
     * @throws UnexpectedValueException
     */
    public function route($path, $controller, $method = NULL, $name = NULL, array $parameters = array())
    {
        if (!in_array($method, array(NULL, 'GET', 'POST', 'PUT', 'DELETE'))) {
            throw new UnexpectedValueException('Unexpected route method:' . $method);
        }
        $controller_name          = is_string($controller) ? $controller : $this->controllers->getNextName();
        $parameters['controller'] = $controller_name;
        $this->controllers->register($controller_name, $controller);

        $route = new Route($path, $method, $parameters);
        return $this->router->route($route, $name);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string $name
     * @param array $parameters
     * @return Route
     */
    public function get($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'GET', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string $name
     * @param array $parameters
     * @return Route
     */
    public function post($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'POST', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string $name
     * @param array $parameters
     * @return Route
     */
    public function put($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'PUT', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string $name
     * @param array $parameters
     * @return Route
     */
    public function delete($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'DELETE', $name, $parameters);
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
            $action   = isset($request->get['action']) ? $request->get['action'] : NULL;
            $this->resolver->resolve($request->get['controller'], $action, $request, $response);
        }

        $this->events->raiseEvent('filter_response', $request, $response);
        return $response;
    }
}
