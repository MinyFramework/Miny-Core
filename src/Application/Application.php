<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Session;
use Miny\Routing\Resources;
use Miny\Routing\Route;
use Miny\Routing\Router;
use UnexpectedValueException;

class Application extends BaseApplication
{

    protected function setDefaultParameters(ParameterContainer $parameterContainer)
    {
        parent::setDefaultParameters($parameterContainer);
        $parameterContainer->addParameters(
            array(
                'router'      => array(
                    'prefix'             => '/',
                    'suffix'             => '',
                    'default_parameters' => array(),
                    'exception_paths'    => array(),
                    'short_urls'         => false
                ),
                'controllers' => array(
                    'namespace' => '\\Application\\Controllers\\'
                )
            )
        );
    }

    protected function registerDefaultServices(Container $container)
    {
        $container->addAlias('\\Miny\\Application\\BaseApplication', __CLASS__);
        $container->addAlias(
            '\\Miny\\HTTP\\AbstractHeaderSender',
            '\\Miny\\HTTP\\NativeHeaderSender'
        );

        parent::registerDefaultServices($container);

        $container->addCallback(
            '\Miny\Event\EventDispatcher',
            function (EventDispatcher $events, Container $container) {
                $eventHandlers = $container->get(
                    '\\Miny\\Application\\Handlers\\ApplicationEventHandlers'
                );
                $events->register(
                    CoreEvents::FILTER_REQUEST,
                    array($eventHandlers, 'logRequest')
                );
                $events->register(
                    CoreEvents::FILTER_REQUEST,
                    array($eventHandlers, 'filterRoutes')
                );
                $events->register(
                    CoreEvents::FILTER_RESPONSE,
                    array($eventHandlers, 'setContentType')
                );
                $events->register(
                    CoreEvents::FILTER_RESPONSE,
                    array($eventHandlers, 'logResponse')
                );
            }
        );

        $container->addConstructorArguments(
            '\\Miny\\Controller\\ControllerCollection',
            '@controllers:namespace'
        );
        $container->addConstructorArguments(
            '\\Miny\\Routing\\Router',
            '@router:prefix',
            '@router:suffix',
            '@router:default_parameters',
            '@router:short_urls'
        );
        $container->addCallback(
            '\\Miny\\HTTP\\Session',
            function (Session $session) {
                $session->open();
            }
        );
    }

    protected function onRun()
    {
        $container = $this->getContainer();

        /** @var $router Router */
        $router = $container->get('\\Miny\\Routing\\Router');

        /** @var $dispatcher Dispatcher */
        $dispatcher = $container->get('\\Miny\\Application\\Dispatcher');

        if (!$router->hasRoute('root')) {
            $this->root('index');
        }
        $dispatcher->dispatch(Request::getGlobal())->send();
    }

    private function registerController($controller, $name = null)
    {
        return $this
            ->getContainer()
            ->get('\\Miny\\Controller\\ControllerCollection')
            ->register($controller, $name);
    }

    /**
     * @param mixed $controller
     * @param array $parameters
     *
     * @return Route
     */
    public function root($controller, array $parameters = array())
    {
        $parameters['controller'] = $this->registerController($controller);

        return $this->getContainer()->get('\\Miny\\Routing\\Router')->root($parameters);
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
    public function route(
        $path,
        $controller,
        $method = null,
        $name = null,
        array $parameters = array()
    ) {
        $parameters['controller'] = $this->registerController($controller);

        return $this->getContainer()->get('\\Miny\\Routing\\Router')->route(
            new Route($path, $method, $parameters),
            $name
        );
    }

    /**
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     * @param array       $parameters
     *
     * @return Route
     */
    public function get($path, $controller, $name = null, array $parameters = array())
    {
        return $this->route($path, $controller, 'GET', $name, $parameters);
    }

    /**
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     * @param array       $parameters
     *
     * @return Route
     */
    public function post($path, $controller, $name = null, array $parameters = array())
    {
        return $this->route($path, $controller, 'POST', $name, $parameters);
    }

    /**
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     * @param array       $parameters
     *
     * @return Route
     */
    public function put($path, $controller, $name = null, array $parameters = array())
    {
        return $this->route($path, $controller, 'PUT', $name, $parameters);
    }

    /**
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     * @param array       $parameters
     *
     * @return Route
     */
    public function delete($path, $controller, $name = null, array $parameters = array())
    {
        return $this->route($path, $controller, 'DELETE', $name, $parameters);
    }
}
