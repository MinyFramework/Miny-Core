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
use UnexpectedValueException;

require_once __DIR__ . '/BaseApplication.php';

class Application extends BaseApplication
{

    protected function setDefaultParameters()
    {
        $this->getFactory()->getParameters()->addParameters(array(
            'router'      => array(
                'prefix'             => '/',
                'suffix'             => '',
                'default_parameters' => array(),
                'exception_paths'    => array(),
                'short_urls'         => false
            ),
            'controllers' => array(
                'namespace' => '\Application\Controllers\\'
            )
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
                $response = $app->response;
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
                ->setArguments('&app', '{@controllers:namespace}');
        $this->add('router', '\Miny\Routing\Router')
                ->setArguments('@router:prefix', '@router:suffix', '@router:default_parameters', '@router:short_urls');
        $this->add('session', '\Miny\Session\Session')
                ->addMethodCall('open');
        $this->add('controller', '\Miny\Controller\Controller')
                ->setArguments('&app');
        $this->add('response', '\Miny\HTTP\Response');
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

    private function registerController($controller)
    {
        $controller_name = is_string($controller) ? $controller : $this->controllers->getNextName();
        $this->controllers->register($controller_name, $controller);
        return $controller_name;
    }

    /**
     *
     * @param mixed $controller
     * @param array $parameters
     * @return Route
     */
    public function root($controller, array $parameters = array())
    {
        $parameters['controller'] = $this->registerController($controller);
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
        $parameters['controller'] = $this->registerController($controller);

        $method = strtoupper($method);
        $route  = new Route($path, $method, $parameters);
        return $this->router->route($route, $name);
    }

    public function __call($method, $args)
    {
        switch ($method) {
            case 'get':
            case 'post':
            case 'put':
            case 'delete':
                array_splice($args, 2, 0, $method);
                call_user_func_array(array($this, 'route'), $args);
                break;
            default:
                return parent::__call($method, $args);
        }
    }

    protected function onRun()
    {
        if (!$this->router->hasRoute('root')) {
            $this->root('index');
        }
        $this->dispatch(Request::getGlobal())->send();
    }

    /**
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request)
    {
        $this->request = $request;
        $event         = $this->events->raiseEvent('filter_request', $request);

        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                $response = $rsp;
            } elseif ($rsp instanceof Request && $rsp !== $request) {
                return $this->dispatch($rsp);
            }
        }

        if (!isset($response)) {
            $factory      = $this->getFactory();
            $old_response = $factory->replace('response');
            ob_start();
            $this->controllers->resolve($request->get['controller'], $request, $this->response);
            $this->response->addContent(ob_get_clean());
            $response     = $old_response ? $factory->replace('response', $old_response) : $factory->response;
        }

        $this->events->raiseEvent('filter_response', $request, $response);
        return $response;
    }
}
