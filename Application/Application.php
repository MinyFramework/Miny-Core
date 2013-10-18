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
use Miny\Application\Exceptions\BadModuleException;
use Miny\AutoLoader;
use Miny\Event\Event;
use Miny\Factory\Factory;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\Log;
use Miny\Routing\Resource;
use Miny\Routing\Resources;
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

    /**
     *
     * @param string $directory
     * @param int $environment
     * @param boolean $include_configs
     */
    public function __construct($directory, $environment = self::ENV_PROD, $include_configs = true)
    {
        $this->environment = $environment;
        $this->autoloader = new AutoLoader(
                array(
            '\Application' => $directory,
            '\Miny'        => __DIR__ . '/..',
            '\Modules'     => __DIR__ . '/../../Modules'
        ));
        $this->setParameters(array(
            'default_timezone' => 'UTC',
            'root'             => $directory,
            'log_path'         => $directory . '/logs',
            'view'             => array(
                'dir'            => $directory . '/templates',
                'default_format' => '.{@router:defaults:format}',
                'exception'      => 'layouts/exception'
            ),
            'router'           => array(
                'prefix'          => '/',
                'suffix'          => '.:format',
                'defaults'        => array(
                    'format' => 'html'
                ),
                'exception_paths' => array()
            ),
            'site'             => array(
                'title'    => 'Miny 1.0',
                'base_url' => 'http://' . $_SERVER['HTTP_HOST'] . '{@router:prefix}'
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
        if (isset($this['modules']) && is_array($this['modules'])) {
            foreach ($this['modules'] as $module) {
                $this->module($module);
            }
        }
    }

    /**
     *
     * @param string $file
     * @param int $env
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
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

    /**
     *
     * @return int
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     *
     * @return boolean
     */
    public function isDeveloperEnvironment()
    {
        return $this->environment == self::ENV_DEV;
    }

    /**
     *
     * @return boolean
     */
    public function isProductionEnvironment()
    {
        return $this->environment == self::ENV_PROD;
    }

    /**
     *
     * @param string $module
     * @throws BadModuleException
     */
    public function module($module)
    {
        if (isset($this->modules[$module])) {
            return;
        }

        $this->log->write(sprintf('Loading Miny module: %s', $module));
        $class = '\Modules\\' . $module . '\Module';
        if (!is_subclass_of($class, __NAMESPACE__ . '\Module')) {
            throw new BadModuleException('Module descriptor should extend Module class: ' . $class);
        }
        $module_class = new $class($this);
        $this->modules[$module] = $module_class;
        foreach ($module_class->getDependencies() as $name) {
            $this->module($name);
        }
        if (func_num_args() == 1) {
            $module_class->init($this);
        } else {
            $args = func_get_args();
            $args[0] = $this;
            call_user_func_array(array($module_class, 'init'), $args);
        }
    }

    private function registerDefaultServices()
    {
        $app = $this;

        set_exception_handler(function(Exception $e) use($app) {
            $event = new Event('uncaught_exception', $e);
            $app->events->raiseEvent($event);
            if (!$event->isHandled()) {
                throw $e;
            } else {
                $response = new Response;
                echo $event->getResponse();
                $response->setCode(500);
                $response->send();
            }
        });

        $log = new Log($this['log_path']);

        set_error_handler(function($errno, $errstr, $errfile, $errline ) use($log) {
            $loggable = array(
                E_NOTICE       => 'Notice (PHP)',
                E_USER_NOTICE  => 'Notice',
                E_WARNING      => 'Warning (PHP)',
                E_USER_WARNING => 'Warning',
                E_DEPRECATED   => 'Deprecated notice (PHP)',
                E_STRICT       => 'Strict notice (PHP)'
            );
            if (isset($loggable[$errno])) {
                $log->write(sprintf("%s in %s on line %s", $errstr, $errfile, $errline), $loggable[$errno]);
            } else {
                throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
            }
        });

        $this->log = $log;
        $eh = new ApplicationEventHandlers($this, $log);

        $this->add('events', '\Miny\Event\EventDispatcher')
                ->setArguments('&log')
                ->addMethodCall('register', 'uncaught_exception', array($eh, 'logException'))
                ->addMethodCall('register', 'uncaught_exception', array($eh, 'displayExceptionPage'))
                ->addMethodCall('register', 'filter_request', array($eh, 'logRequest'))
                ->addMethodCall('register', 'filter_request', array($eh, 'filterRoutes'))
                ->addMethodCall('register', 'filter_response', array($eh, 'setContentType'))
                ->addMethodCall('register', 'filter_response', array($eh, 'logResponse'))
                ->addMethodCall('register', 'invalid_response', array($eh, 'filterStringToResponse'));

        $this->add('view_factory', '\Miny\View\ViewFactory')
                ->addMethodCall('setDirectory', '@view:dir')
                ->addMethodCall('setSuffix', '@view:default_format')
                ->addMethodCall('setHelpers', '&view_helpers')
                ->addMethodCall('addViewType', 'view', '\Miny\View\View')
                ->addMethodCall('addViewType', 'list', '\Miny\View\ListView')
                ->setProperty('config', '&app::getResolvedParameters');

        $this->add('view_helpers', '\Miny\View\ViewHelpers')
                ->addMethodCall('addMethod', 'route', '*router::generate')
                ->addMethodCall('addMethod', 'routeAnchor',
                        function($route, $label, array $parameters = array(), array $args = array()) use($app) {
                    $url = $app->router->generate($route, $parameters);
                    return $app->view_helpers->anchor($url, $label, $args);
                });

        $this->add('controllers', '\Miny\Controller\ControllerCollection')
                ->setArguments('&app');
        $this->add('resolver', '\Miny\Controller\ControllerResolver')
                ->setArguments('&controllers');
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

    /**
     *
     * @param string $name
     * @param mixed $controller
     * @param array $parameters
     * @return Resource
     */
    public function resource($name, $controller = NULL, array $parameters = array())
    {
        $parameters['controller'] = $controller ? : $name;
        return $this->router->resource($name, $parameters);
    }

    /**
     *
     * @param string $name
     * @param mixed $controller
     * @param array $parameters
     * @return Resources
     */
    public function resources($name, $controller = NULL, array $parameters = array())
    {
        $parameters['controller'] = $controller ? : $name;
        return $this->router->resources($name, $parameters);
    }

    /**
     *
     * @param mixed $controller
     * @param array $parameters
     * @return Route
     */
    public function root($controller, array $parameters = array())
    {
        $controller_name = is_string($controller) ? $controller : $this->controllers->getNextName();
        $parameters['controller'] = $controller_name;
        $this->controllers->register($controller_name, $controller);
        return $this->router->root($parameters);
    }

    /**
     *
     * @param type $path
     * @param type $controller
     * @param type $method
     * @param type $name
     * @param array $parameters
     * @return Route
     * @throws UnexpectedValueException
     */
    public function route($path, $controller, $method = NULL, $name = NULL, array $parameters = array())
    {
        if (!in_array($method, array(NULL, 'GET', 'POST', 'PUT', 'DELETE'))) {
            throw new UnexpectedValueException('Unexpected route method:' . $method);
        }
        $controller_name = is_string($controller) ? $controller : $this->controllers->getNextName();
        $parameters['controller'] = $controller_name;
        $this->controllers->register($controller_name, $controller);

        $route = new Route($path, $method, $parameters);
        return $this->router->route($route, $name);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string $name
     * @param array $parameters
     * @return \Miny\Routing\Route
     */
    public function get($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'GET', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string $name
     * @param array $parameters
     * @return \Miny\Routing\Route
     */
    public function post($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'POST', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string $name
     * @param array $parameters
     * @return \Miny\Routing\Route
     */
    public function put($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'PUT', $name, $parameters);
    }

    /**
     *
     * @param string $path
     * @param mixed $controller
     * @param string $name
     * @param array $parameters
     * @return \Miny\Routing\Route
     */
    public function delete($path, $controller, $name = NULL, array $parameters = array())
    {
        return $this->route($path, $controller, 'DELETE', $name, $parameters);
    }

    /**
     *
     */
    public function run()
    {
        date_default_timezone_set($this['default_timezone']);
        $this->dispatch($this->request)->send();
    }

    /**
     *
     * @param \Miny\HTTP\Request $request
     * @return \Miny\HTTP\Response
     */
    public function dispatch(Request $request)
    {
        $event = new Event('filter_request', $request);
        $this->events->raiseEvent($event);

        if ($event->hasResponse()) {
            $rsp = $event->getResponse();
            if ($rsp instanceof Response) {
                $response = $rsp;
            } elseif ($rsp instanceof Request && $rsp !== $request) {
                return $this->dispatch($rsp);
            }
        }

        if (!isset($response)) {
            $response = new Response;
            $action = isset($request->get['action']) ? $request->get['action'] : NULL;
            $this->resolver->resolve($request->get['controller'], $action, $request, $response);
        }

        $this->events->raiseEvent(new Event('filter_response', $request, $response));
        return $response;
    }

}
