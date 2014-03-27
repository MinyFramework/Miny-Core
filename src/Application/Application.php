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
        $container->addAlias('\\Miny\\Application\\BaseApplication', __CLASS__);
        $container->addAlias(
            '\\Miny\\HTTP\\AbstractHeaderSender',
            '\\Miny\\HTTP\\NativeHeaderSender'
        );
        $container->addAlias(
            '\\Miny\\Router\\AbstractRouteParser',
            '\\Miny\\Router\\RouteParser'
        );

        parent::registerDefaultServices($container);

        $eventHandlers = $container->get('\\Miny\\Application\\Handlers\\ApplicationEventHandlers');

        $events = $container->get('\\Miny\\Event\\EventDispatcher');
        $events->register(CoreEvents::FILTER_REQUEST, array($eventHandlers, 'logRequest'));
        $events->register(CoreEvents::FILTER_REQUEST, array($eventHandlers, 'filterRoutes'));
        $events->register(CoreEvents::FILTER_RESPONSE, array($eventHandlers, 'setContentType'));
        $events->register(CoreEvents::FILTER_RESPONSE, array($eventHandlers, 'logResponse'));

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

        $container->addConstructorArguments(
            '\\Miny\\Router\\RouteGenerator',
            null,
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
        $router = $container->get('\\Miny\\Router\\Router');

        /** @var $dispatcher Dispatcher */
        $dispatcher = $container->get('\\Miny\\Application\\Dispatcher');

        if (!$router->has('root')) {
            $router->root()->set('controller', 'index');
        }
        $dispatcher->dispatch(Request::getGlobal())->send();
    }
}
