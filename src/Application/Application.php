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

        $this->eventDispatcher->registerHandlers(
            CoreEvents::FILTER_REQUEST,
            [
                [$eventHandlers, 'logRequest'],
                [$eventHandlers, 'filterRoutes']
            ]
        );

        $this->eventDispatcher->registerHandlers(
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
            function (Router $router) {
                $parameterContainer = $this->parameterContainer;

                $router->addGlobalValues($parameterContainer['router:default_parameters']);
                $router->setPrefix($parameterContainer['router:prefix']);
                $router->setPostfix($parameterContainer['router:postfix']);

                $this->eventDispatcher->register(
                    CoreEvents::BEFORE_RUN,
                    [$router, 'registerResources']
                );
            }
        );

        $container->addAlias(
            'Miny\\HTTP\\Request',
            ['Miny\\HTTP\\Request', 'getGlobal']
        );

        $routeGeneratorClass = 'Miny\\Router\\RouteGenerator';
        $container->setConstructorArgument($routeGeneratorClass, 1, '@router:short_urls');
    }

    protected function onRun()
    {
        $this->ensureRootRoute();

        /** @var $request Request */
        $request = $this->container->get('Miny\\HTTP\\Request');
        /** @var $dispatcher Dispatcher */
        $dispatcher = $this->container->get('Miny\\Application\\Dispatcher');
        $dispatcher->dispatch($request)->send();
    }

    private function ensureRootRoute()
    {
        /** @var $router Router */
        $router = $this->container->get('Miny\\Router\\Router');
        if (!$router->has('root')) {
            $router->root()->set('controller', 'index');
        }
    }
}
