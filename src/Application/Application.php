<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Controller\ControllerDispatcher;
use Miny\CoreEvents;
use Miny\Event\EventDispatcher;
use Miny\Factory\Container;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Session;
use Miny\Router\Route;
use Miny\Router\Router;
use UnexpectedValueException;

class Application extends BaseApplication
{

    protected function setDefaultParameters(ParameterContainer $parameterContainer)
    {
        parent::setDefaultParameters($parameterContainer);
        $parameterContainer->addParameters(
            array(
                'router' => array(
                    'prefix'             => '/',
                    'postfix'            => '',
                    'default_parameters' => array(),
                    'exception_paths'    => array(),
                    'short_urls'         => false
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
            '\\Miny\\Event\\EventDispatcher',
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

        $container->addCallback(
            '\\Miny\\Controller\\ControllerDispatcher',
            function (ControllerDispatcher $dispatcher, Container $container) {
                $dispatcher->addRunner(
                    $container->get('\\Miny\\Controller\\Runners\\StringControllerRunner')
                );
                $dispatcher->addRunner(
                    $container->get('\\Miny\\Controller\\Runners\\ClosureControllerRunner')
                );
            }
        );

        $parameterContainer = $this->getParameterContainer();
        $container->addCallback(
            '\\Miny\\Router\\Router',
            function (Router $router) use ($parameterContainer) {
                $router->addGlobalValues($parameterContainer['router:default_parameters']);
                $router->setPrefix($parameterContainer['router:prefix']);
                $router->setPostfix($parameterContainer['router:postfix']);
            }
        );

        $container->addAlias(
            '\\Miny\\Router\\RouteGenerator',
            null,
            array(1 => '@router:short_urls')
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
        $router = $container->get('\\Miny\\Router\\Router');

        /** @var $dispatcher Dispatcher */
        $dispatcher = $container->get('\\Miny\\Application\\Dispatcher');

        if (!$router->has('root')) {
            $router->root()->set('controller', 'index');
        }
        $dispatcher->dispatch(Request::getGlobal())->send();
    }

    /**
     * @param mixed $controller
     *
     * @return Route
     */
    public function root($controller)
    {
        /** @var $router Router */
        $router = $this->getContainer()->get('\\Miny\\Router\\Router');

        return $router->root()->set('controller', $controller);
    }

    /**
     *
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $method
     * @param string|null $name
     *
     * @return Route
     *
     * @throws UnexpectedValueException
     */
    public function route($path, $controller, $method = null, $name = null)
    {
        /** @var $router Router */
        $router = $this->getContainer()->get('\\Miny\\Router\\Router');

        return $router->add($path, $method, $name)->set('controller', $controller);
    }

    /**
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     *
     * @return Route
     */
    public function get($path, $controller, $name = null)
    {
        /** @var $router Router */
        $router = $this->getContainer()->get('\\Miny\\Router\\Router');

        return $router->get($path, $name)->set('controller', $controller);
    }

    /**
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     *
     * @return Route
     */
    public function post($path, $controller, $name = null)
    {
        /** @var $router Router */
        $router = $this->getContainer()->get('\\Miny\\Router\\Router');

        return $router->post($path, $name)->set('controller', $controller);
    }

    /**
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     *
     * @return Route
     */
    public function put($path, $controller, $name = null)
    {
        /** @var $router Router */
        $router = $this->getContainer()->get('\\Miny\\Router\\Router');

        return $router->put($path, $name)->set('controller', $controller);
    }

    /**
     * @param string      $path
     * @param mixed       $controller
     * @param string|null $name
     *
     * @return Route
     */
    public function delete($path, $controller, $name = null)
    {
        /** @var $router Router */
        $router = $this->getContainer()->get('\\Miny\\Router\\Router');

        return $router->delete($path, $name)->set('controller', $controller);
    }
}
