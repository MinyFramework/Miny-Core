<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

require_once __DIR__ . '/../AutoLoader.php';
require_once __DIR__ . '/../Factory/Factory.php';

use ErrorException;
use Exception;
use InvalidArgumentException;
use Miny\AutoLoader;
use Miny\Event\Event;
use Miny\Factory\Factory;
use Miny\HTTP\Request;
use Miny\Log;
use Miny\Routing\Route;
use Miny\Session\Session;
use UnexpectedValueException;

class Application extends Factory
{
    const ENV_PROD = 0;
    const ENV_DEV = 1;
    const ENV_COMMON = 2;

    private $modules = array();
    private $environment;

    public function __construct($directory, $environment = self::ENV_PROD, $include_configs = true)
    {
        $this->environment = $environment;
        $this->autoloader = new AutoLoader(
                        array(
                            '\Application' => $directory,
                            '\Miny'        => __DIR__ . '/../',
                            '\Modules'     => __DIR__ . '/../../Modules'
                ));
        $this->setParameters(array(
            'default_timezone' => date_default_timezone_get(),
            'root'             => $directory,
            'log_path'         => $directory . '/logs',
            'view'             => array(
                'dir'            => $directory . '/views',
                'default_format' => 'html',
                'exception'      => 'layouts/exception'
            ),
            'router'         => array(
                'prefix'   => '/',
                'suffix'   => '.:format',
                'defaults' => array(
                    'format'          => '{@view:default_format}'
                ),
                'exception_paths' => array()
            )
        ));
        $this->setInstance('app', $this);
        if ($include_configs) {
            $config_files = array(
                $directory . '/config/config.common.php' => self::ENV_COMMON,
                $directory . '/config/config.dev.php'    => self::ENV_DEV,
                $directory . '/config/config.php'        => self::ENV_PROD
            );
            foreach ($config_files as $file => $env) {
                try {
                    $this->loadConfig($file, $env);
                } catch (InvalidArgumentException $e) {

                }
            }
        }
        $this->registerDefaultServices();
        $env = ($environment == self::ENV_PROD) ? 'production' : 'development';
        $this->log->write(sprintf('Starting Miny in %s environment', $env));
    }

    public function loadConfig($file, $env = self::ENV_COMMON)
    {
        if ($env != $this->environment && $env != self::ENV_COMMON) {
            return;
        }
        if (!is_file($file)) {
            throw new InvalidArgumentException('Configuration file not found: ' . $file);
        }
        $config = include $file;
        if (!is_array($config)) {
            throw new UnexpectedValueException('Invalid configuration file: ' . $file);
        }
        $this->setParameters($config);
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function isDeveloperEnvironment()
    {
        return $this->environment == self::ENV_DEV;
    }

    public function isProductionEnvironment()
    {
        return $this->environment == self::ENV_PROD;
    }

    public function module($module)
    {
        if (isset($this->modules[$module])) {
            return;
        }
        $class = '\Modules\\' . $module . '\Module';
        $module_class = new $class;
        if (!$module_class instanceof Module) {
            throw new UnexpectedValueException('Module descriptor should extend Module class.');
        }
        $this->modules[$module] = $module_class;
        foreach (array_keys($module_class->getDependencies()) as $name) {
            $this->module($name);
        }
        $args = func_get_args();
        array_shift($args);
        if (empty($args)) {
            $module_class->init($this);
        } else {
            array_unshift($args, $this);
            call_user_func_array(array($module_class, 'init'), $args);
        }
    }

    private function registerDefaultServices()
    {
        $app = $this;

        set_exception_handler(function(Exception $e) use($app) {
                    $event = new Event('uncaught_exception', array('exception' => $e));
                    $app->events->raiseEvent($event);
                    if (!$event->isHandled()) {
                        throw $e;
                    }
                });
        set_error_handler(function($errno, $errstr, $errfile, $errline ) {
                    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
                });

        $this->log = new Log($this['log_path']);
        $eh = new ApplicationEventHandlers($this);

        $this->add('events', '\Miny\Event\EventDispatcher')
                ->setArguments('&log')
                ->addMethodCall('setHandler', 'handle_exception', $eh)
                ->addMethodCall('setHandler', 'handle_exception', $eh, 'displayExceptionPage')
                ->addMethodCall('setHandler', 'uncaught_exception', $eh)
                ->addMethodCall('setHandler', 'handle_request_exception', $eh, 'handleRequestException')
                ->addMethodCall('setHandler', 'filter_request', $eh, 'filterRoutes')
                ->addMethodCall('setHandler', 'invalid_response', $eh, 'filterStringToResponse');

        $this->add('view', '\Miny\View\View')
                ->setArguments('@view:dir', '@view:default_format')
                ->addMethodCall('addMethod', 'route', '*router::generate')
                ->addMethodCall('addMethod', 'filter_escape', 'htmlspecialchars')
                ->addMethodCall('addMethod', 'filter_json', 'json_encode')
                ->addMethodCall('addMethod', 'anchor',
                        function($url, $label) {
                            return '<a href="' . $url . '">' . $label . '</a>';
                        })
                ->addMethodCall('addMethod', 'routeAnchor',
                        function($url, $label, array $params = array()) use($app) {
                            $route = $app->router->generate($url, $params);
                            return '<a href="' . $route . '">' . $label . '</a>';
                        })
                ->addMethodCall('addMethod', 'arguments',
                        function(array $args) {
                            $arglist = '';
                            foreach ($args as $name => $value) {
                                $arglist .= ' ' . $name . '="' . $value . '"';
                            }
                            return $arglist;
                        });

        $this->add('validator', '\Miny\Validator\Validator');
        $this->add('controllers', '\Miny\Controller\ControllerCollection');
        $this->add('resolver', '\Miny\Controller\ControllerResolver')
                ->setArguments('&app', '&controllers');
        $this->add('dispatcher', '\Miny\HTTP\Dispatcher')
                ->setArguments('&events', '&resolver');
        $this->add('router', '\Miny\Routing\Router')
                ->setArguments('@router:prefix', '@router:suffix', '@router:defaults');

        $session = new Session;
        $session->open();

        if (!isset($session['token'])) {
            $session['token'] = sha1(mt_rand());
        }

        $this->session = $session;
        $this->request = Request::getGlobal();
    }

    public function route($path, $controller, $method = NULL, $name = NULL, array $parameters = array())
    {
        $controller_name = $this->controllers->getNextName();
        if (!in_array($method, array(NULL, 'GET', 'POST', 'PUT', 'DELETE'))) {
            throw new UnexpectedValueException('Unexpected route method:' . $method);
        }
        $parameters['controller'] = $controller_name;
        $route = new Route($path, $method, $parameters);
        $this->router->route($route, $name);
        $this->controllers->register($controller_name, $controller);
        return $route;
    }

    public function resource($name, $controller = NULL, array $parameters = array(), $singular = false)
    {
        $controller = $controller ? : $name;
        $parameters['controller'] = $controller;
        return $this->router->resource($name, $parameters, $singular);
    }

    public function root($controller, array $parameters = array())
    {
        $controller_name = $this->controllers->getNextName();
        $parameters['controller'] = $controller_name;
        $this->controllers->register($controller_name, $controller);
        return $this->router->root($parameters);
    }

    public function get($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'GET', $name, $parameters);
    }

    public function post($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'POST', $name, $parameters);
    }

    public function put($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'PUT', $name, $parameters);
    }

    public function delete($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'DELETE', $name, $parameters);
    }

    public function run()
    {
        date_default_timezone_set($this['default_timezone']);
        $request = $this->request;
        $this->log->write(sprintf('Request: [%s] %s Source: %s', $request->method, $request->path, $request->ip));
        $response = $this->dispatcher->dispatch($request);
        $this->log->write('Response: ' . $response->getStatus());
        $response->send();
    }

}
