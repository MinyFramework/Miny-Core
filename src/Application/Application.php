<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Factory\Container;
use Miny\HTTP\Request;
use Miny\HTTP\Session;
use Miny\Routing\Resources;
use Miny\Routing\Route;
use UnexpectedValueException;

class Application extends BaseApplication
{

    protected function setDefaultParameters()
    {
        parent::setDefaultParameters();
        $this->getParameterContainer()->addParameters(
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

    protected function registerDefaultServices(Container $factory)
    {
        parent::registerDefaultServices($factory);

        $events = $factory->get('\Miny\Event\EventDispatcher');

        $eventHandlers = $factory->get('\Miny\Application\Handlers\ApplicationEventHandlers');
        $events->register('filter_request', array($eventHandlers, 'logRequest'));
        $events->register('filter_request', array($eventHandlers, 'filterRoutes'));
        $events->register('filter_response', array($eventHandlers, 'setContentType'));
        $events->register('filter_response', array($eventHandlers, 'logResponse'));

        $factory->addAlias('\Miny\Controller\ControllerCollection', null, array(1 => '@controllers:namespace'));
        $factory->addAlias(
            '\Miny\Routing\Router',
            null,
            array(
                '@router:prefix',
                '@router:suffix',
                '@router:default_parameters',
                '@router:short_urls'
            )
        );
        $factory->addCallback(
            '\Miny\HTTP\Session',
            function (Session $session) {
                $session->open();
            }
        );
    }

    protected function onRun()
    {
        $factory = $this->getContainer();
        if (!$factory->get('\Miny\Routing\Router')->hasRoute('root')) {
            $this->root('index');
        }
        $factory->get('\Miny\Application\Dispatcher')->dispatch(Request::getGlobal())->send();
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

        return $this->getContainer()->get('\Miny\Routing\Router')->root($parameters);
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

        return $this->getContainer()->get('\Miny\Routing\Router')->resource($name, $parameters);
    }

    private function registerController($controller, $name = null)
    {
        return $this->getContainer()->get('\Miny\Controller\ControllerCollection')->register($controller, $name);
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

        return $this->getContainer()->get('\Miny\Routing\Router')->resources($name, $parameters);
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

        return $this->getContainer()->get('\Miny\Routing\Router')->route($route, $name);
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
