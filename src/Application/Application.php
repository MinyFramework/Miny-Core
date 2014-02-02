<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Application\Handlers\ApplicationEventHandlers;
use Miny\Factory\Factory;
use Miny\HTTP\Request;
use Miny\Routing\Resources;
use Miny\Routing\Route;
use UnexpectedValueException;

class Application extends BaseApplication
{

    protected function setDefaultParameters()
    {
        parent::setDefaultParameters();
        $this->getFactory()->getParameters()->addParameters(
            array(
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
            )
        );
    }

    protected function registerDefaultServices(Factory $factory)
    {
        parent::registerDefaultServices($factory);

        $event_handlers = new ApplicationEventHandlers($factory, $factory->get('log'));
        $factory->getBlueprint('events')
            ->addMethodCall('register', 'filter_request', array($event_handlers, 'logRequest'))
            ->addMethodCall('register', 'filter_request', array($event_handlers, 'filterRoutes'))
            ->addMethodCall('register', 'filter_response', array($event_handlers, 'setContentType'))
            ->addMethodCall('register', 'filter_response', array($event_handlers, 'logResponse'));

        $factory->add('controllers', '\Miny\Controller\ControllerCollection')
            ->setArguments($this, '{@controllers:namespace}');
        $factory->add('controller', '\Miny\Controller\Controller')
            ->setArguments($this);
        $factory->add('router', '\Miny\Routing\Router')
            ->setArguments('@router:prefix', '@router:suffix', '@router:default_parameters', '@router:short_urls');
        $factory->add('session', '\Miny\HTTP\Session')
            ->addMethodCall('open');
        $factory->add('response', '\Miny\HTTP\Response');
        $factory->add('dispatcher', '\Miny\Application\Dispatcher')
            ->setArguments($factory);
    }

    protected function onRun()
    {
        $factory = $this->getFactory();
        if (!$factory->get('router')->hasRoute('root')) {
            $this->root('index');
        }
        $factory->get('dispatcher')->dispatch(Request::getGlobal())->send();
    }

    /**
     *
     * @param mixed $controller
     * @param array $parameters
     *
     * @return Route
     */
    public function root($controller, array $parameters = array())
    {
        $parameters['controller'] = $this->registerController($controller);

        return $this->getFactory()->get('router')->root($parameters);
    }

    /**
     *
     * @param string $name
     * @param mixed  $controller
     * @param array  $parameters
     *
     * @return Resource
     */
    public function resource($name, $controller = null, array $parameters = array())
    {
        $parameters['controller'] = $this->registerController($controller ? : $name, $name);

        return $this->getFactory()->get('router')->resource($name, $parameters);
    }

    private function registerController($controller, $name = null)
    {
        return $this->getFactory()->get('controllers')->register($controller, $name);
    }

    /**
     *
     * @param string $name
     * @param mixed  $controller
     * @param array  $parameters
     *
     * @return Resources
     */
    public function resources($name, $controller = null, array $parameters = array())
    {
        $parameters['controller'] = $this->registerController($controller ? : $name, $name);

        return $this->getFactory()->get('router')->resources($name, $parameters);
    }

    /**
     *
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     * @param array       $parameters
     */
    public function get($path, $controller, $name = null, array $parameters = array())
    {
        $this->route($path, $controller, 'GET', $name, $parameters);
    }

    /**
     *
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $method
     * @param string|null $name
     * @param array       $parameters
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
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     * @param array       $parameters
     */
    public function post($path, $controller, $name = null, array $parameters = array())
    {
        $this->route($path, $controller, 'POST', $name, $parameters);
    }

    /**
     *
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     * @param array       $parameters
     */
    public function put($path, $controller, $name = null, array $parameters = array())
    {
        $this->route($path, $controller, 'PUT', $name, $parameters);
    }

    /**
     *
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     * @param array       $parameters
     */
    public function delete($path, $controller, $name = null, array $parameters = array())
    {
        $this->route($path, $controller, 'DELETE', $name, $parameters);
    }
}
