<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Application\Handlers\ApplicationEventHandlers;
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
        parent::setDefaultParameters();
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

    protected function registerEventHandlers()
    {
        $factory = $this->getFactory();

        $event_handlers = new ApplicationEventHandlers($factory);
        $factory->getBlueprint('events')
                ->addMethodCall('register', 'filter_request', array($event_handlers, 'logRequest'))
                ->addMethodCall('register', 'filter_request', array($event_handlers, 'filterRoutes'))
                ->addMethodCall('register', 'filter_response', array($event_handlers, 'setContentType'))
                ->addMethodCall('register', 'filter_response', array($event_handlers, 'logResponse'));
    }

    protected function registerDefaultServices()
    {
        parent::registerDefaultServices();
        $this->registerEventHandlers();

        $factory = $this->getFactory();
        $factory->add('controllers', '\Miny\Controller\ControllerCollection')
                ->setArguments($this, '{@controllers:namespace}');
        $factory->add('router', '\Miny\Routing\Router')
                ->setArguments('@router:prefix', '@router:suffix', '@router:default_parameters', '@router:short_urls');
        $factory->add('session', '\Miny\HTTP\Session')
                ->addMethodCall('open');
        $factory->add('controller', '\Miny\Controller\Controller')
                ->setArguments($this);
        $factory->add('response', '\Miny\HTTP\Response');
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
        $parameters['controller'] = $this->registerController($controller ? : $name, $name);
        return $this->getFactory()->get('router')->resource($name, $parameters);
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
        $parameters['controller'] = $this->registerController($controller ? : $name, $name);
        return $this->getFactory()->get('router')->resources($name, $parameters);
    }

    private function registerController($controller, $name = null)
    {
        return $this->getFactory()->get('controllers')->register($controller, $name);
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
        return $this->getFactory()->get('router')->root($parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string|null $method
     * @param string|null $name
     * @param array $parameters
     *
     * @return Route
     *
     * @throws UnexpectedValueException
     */
    public function route($path, $controller, $method = null, $name = null, array $parameters = array())
    {
        $parameters['controller'] = $this->registerController($controller);

        $route = new Route($path, $method, $parameters);
        return $this->getFactory()->get('router')->route($route, $name);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string|null $name
     * @param array $parameters
     */
    public function get($path, $controller, $name = null, array $parameters = array())
    {
        $this->route($path, $controller, 'GET', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string|null $name
     * @param array $parameters
     */
    public function post($path, $controller, $name = null, array $parameters = array())
    {
        $this->route($path, $controller, 'POST', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string|null $name
     * @param array $parameters
     */
    public function put($path, $controller, $name = null, array $parameters = array())
    {
        $this->route($path, $controller, 'PUT', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string|null $name
     * @param array $parameters
     */
    public function delete($path, $controller, $name = null, array $parameters = array())
    {
        $this->route($path, $controller, 'DELETE', $name, $parameters);
    }

    protected function onRun()
    {
        if (!$this->getFactory()->get('router')->hasRoute('root')) {
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
        $factory = $this->getFactory();
        $events  = $factory->get('events');

        $old_request = $factory->replace('request', $request);
        $event       = $events->raiseEvent('filter_request', $request);

        $filter = true;
        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                $response = $rsp;
            } elseif ($rsp instanceof Request && $rsp !== $request) {
                $response = $this->dispatch($rsp);
                $filter   = false;
            }
        }

        ob_start();
        if (!isset($response)) {
            $old_response = $factory->replace('response');
            $factory->get('controllers')->resolve($request->get['controller'], $request, $factory->get('response'));
            $response     = $old_response ? $factory->replace('response', $old_response) : $factory->get('response');
        }

        if ($filter) {
            $events->raiseEvent('filter_response', $request, $response);
        }
        $response->addContent(ob_get_clean());

        if ($old_request) {
            $factory->replace('request', $old_request);
        }
        return $response;
    }
}
