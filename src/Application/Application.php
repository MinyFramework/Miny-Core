<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Controller\ControllerDispatcher;
use Miny\CoreEvents;
use Miny\Factory\Container;
use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\Router\Router;

class Application extends BaseApplication
{

    protected function setDefaultParameters(ParameterContainer $parameterContainer)
    {
        parent::setDefaultParameters($parameterContainer);
        $parameterContainer->addParameters(
            [
                'router'                   => [
                    'prefix'             => '/',
                    'postfix'            => '',
                    'default_parameters' => [],
                    'exception_paths'    => [],
                    'short_urls'         => false
                ],
                'default_content_encoding' => 'utf-8'
            ]
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
            [
                [$eventHandlers, 'logRequest'],
                [$eventHandlers, 'filterRoutes']
            ]
        );

        $events->registerHandlers(
            CoreEvents::FILTER_RESPONSE,
            [
                [$eventHandlers, 'setContentType'],
                [$eventHandlers, 'logResponse']
            ]
        );

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
            function (Router $router) use ($events) {
                $parameterContainer = $this->parameterContainer;

                $router->addGlobalValues($parameterContainer['router:default_parameters']);
                $router->setPrefix($parameterContainer['router:prefix']);
                $router->setPostfix($parameterContainer['router:postfix']);

                $events->register(CoreEvents::BEFORE_RUN, [$router, 'registerResources']);
            }
        );

        $container->addAlias(
            'Miny\\HTTP\\Request',
            function () {
                return Request::getGlobal();
            }
        );

        $routeGeneratorClass = 'Miny\\Router\\RouteGenerator';
        $container->setConstructorArgument($routeGeneratorClass, 1, '@router:short_urls');
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
        /** @var $request Request */
        $request = $this->container->get('Miny\\HTTP\\Request');
        $dispatcher->dispatch($request)->send();
    }
}
