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
use Miny\Factory\Container;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Session;
use Miny\Router\Router;

class Application extends BaseApplication
{

    protected function setDefaultParameters(ParameterContainer $parameterContainer)
    {
        parent::setDefaultParameters($parameterContainer);
        $parameterContainer->addParameters(
            array(
                'router'                   => array(
                    'prefix'             => '/',
                    'postfix'            => '',
                    'default_parameters' => array(),
                    'exception_paths'    => array(),
                    'short_urls'         => false
                ),
                'default_content_encoding' => 'utf-8'
            )
        );
    }

    protected function registerDefaultServices(Container $container)
    {
        $container->addAlias('Miny\\Application\\BaseApplication', __CLASS__);
        $container->addAlias('Miny\\HTTP\\AbstractHeaderSender', 'Miny\\HTTP\\NativeHeaderSender');
        $container->addAlias('Miny\\Router\\AbstractRouteParser', 'Miny\\Router\\RouteParser');

        parent::registerDefaultServices($container);

        $eventHandlers = $container->get('Miny\\Application\\Handlers\\ApplicationEventHandlers');

        $events = $this->eventDispatcher;
        $events->registerHandlers(
            CoreEvents::FILTER_REQUEST,
            array(
                array($eventHandlers, 'logRequest'),
                array($eventHandlers, 'filterRoutes')
            )
        );

        $events->registerHandlers(
            CoreEvents::FILTER_RESPONSE,
            array(
                array($eventHandlers, 'setContentType'),
                array($eventHandlers, 'logResponse')
            )
        );

        $parameterContainer = $this->parameterContainer;

        $container->addCallback(
            'Miny\\Controller\\ControllerDispatcher',
            function (ControllerDispatcher $dispatcher, Container $container) {
                $dispatcher->addRunner(
                    $container->get('Miny\\Controller\\Runners\\StringControllerRunner')
                );
                $dispatcher->addRunner(
                    $container->get('Miny\\Controller\\Runners\\ClosureControllerRunner')
                );
            }
        );
        $container->addCallback(
            'Miny\\Router\\Router',
            function (Router $router) use ($parameterContainer, $events) {
                $router->addGlobalValues($parameterContainer['router:default_parameters']);
                $router->setPrefix($parameterContainer['router:prefix']);
                $router->setPostfix($parameterContainer['router:postfix']);

                $events->register(CoreEvents::BEFORE_RUN, array($router, 'registerResources'));
            }
        );

        $routeGeneratorClass = 'Miny\\Router\\RouteGenerator';
        $container->setConstructorArgument($routeGeneratorClass, 1, '@router:short_urls');

        $container->addCallback(
            'Miny\\HTTP\\Session',
            function (Session $session) {
                $session->open();
            }
        );
    }

    protected function onRun()
    {
        /** @var $router Router */
        $router = $this->container->get('Miny\\Router\\Router');
        if (!$router->has('root')) {
            $router->root()->set('controller', 'index');
        }

        /** @var $dispatcher Dispatcher */
        $dispatcher = $this->container->get('Miny\\Application\\Dispatcher');
        $dispatcher->dispatch(Request::getGlobal())->send();
    }
}
