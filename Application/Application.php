<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/Application
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Application;

use \Miny\Log;
use \Miny\Event\Event;
use \Miny\Factory\Factory;
use \Miny\Routing\Route;
use \Miny\Routing\Resource;
use \Miny\Routing\Resources;
use \Miny\HTTP\Request;

class Application extends Factory
{
    const ENV_PROD = 0;
    const ENV_DEV = 1;
    const ENV_COMMON = 2;

    private $directory;
    private $modules = array();
    private $environment;

    public function __construct($directory, $environment = self::ENV_PROD, $include_configs = true)
    {
        $this->directory = $directory;
        $this->environment = $environment;

        $this->setInstance('app', $this);
        if ($include_configs) {
            $config_files = array(
                $directory . '/config/config.common.php' => self::ENV_COMMON,
                $directory . '/config/config.dev.php'    => self::ENV_DEV,
                $directory . '/config/config.php'        => self::ENV_PROD
            );
            $this->loadConfigs($config_files);
        }
        $this->registerDefaultServices();
        $env = ($environment == self::ENV_PROD) ? 'production' : 'development';
        $this->log->write(sprintf('Starting Miny in %s environment', $env));
    }

    public function loadConfigs(array $files)
    {
        foreach ($files as $file => $env) {
            $this->loadConfig($file, $env);
        }
    }

    public function loadConfig($file, $env = self::ENV_COMMON)
    {
        if ($env != $this->environment && $env != self::ENV_COMMON) {
            return;
        }
        if (!is_file($file)) {
            throw new \InvalidArgumentException('Configuration file not found: ' . $file);
        }
        $config = include $file;
        if (!is_array($config)) {
            throw new \UnexpectedValueException('Invalid configuration file: ' . $file);
        }
        $this->setParameters($config);
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function module($module)
    {
        if (isset($this->modules[$module])) {
            return;
        }
        $class = '\Modules\\' . $module . '\Module';
        $module_class = new $class;
        if (!$module_class instanceof Module) {
            throw new \UnexpectedValueException('Module descriptor should extend Module class.');
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
        set_exception_handler(function(\Exception $e) use($app) {
                    $event = new Event('uncaught_exception', array('exception' => $e));
                    $app->events->raiseEvent($event);
                });
        set_error_handler(function($errno, $errstr, $errfile, $errline ) {
                    throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
                });

        $log_path = isset($this['log_path']) ? $this['log_path'] : $this->directory . '/logs';
        $this->log = new Log($log_path);
        $eh = new ExceptionHandler($this->log);
        $this->add('events', '\Miny\Event\EventDispatcher')
                ->setArguments('&log')
                ->addMethodCall('setHandler', 'handle_exception', $eh)
                ->addMethodCall('setHandler', 'uncaught_exception', $eh);


        $this->add('validator', '\Miny\Validator\Validator');
        $this->add('form_validator', '\Miny\Form\FormValidator');
        $this->add('templating', '\Miny\Template\Template')->setArguments('@template:dir', '@template:default_format');
        $this->add('controllers', '\Miny\Controller\ControllerCollection');
        $this->add('resolver', '\Miny\Controller\ControllerResolver')->setArguments('&app', '&controllers');
        $this->add('dispatcher', '\Miny\HTTP\Dispatcher')->setArguments('&events', '&resolver');

        $session = new \Miny\Session\Session;
        $session->open();

        if (!isset($session['token'])) {
            $session['token'] = sha1(mt_rand());
        }

        $this->session = $session;
        $this->request = Request::getGlobal();

        $router = $this->add('router', '\Miny\Routing\Router');
        if (isset($this['router'])) {
            $router->setArguments('@router:prefix', '@router:suffix', '@router:defaults');
        }
    }

    public function route($path, $controller, $method = NULL, $name = NULL, array $parameters = array())
    {
        $controller_name = $this->controllers->getNextName();
        if (!in_array($method, array(NULL, 'GET', 'POST', 'PUT', 'DELETE'))) {
            throw new \UnexpectedValueException('Unexpected route method:' . $method);
        }
        $parameters['controller'] = $controller_name;
        $route = new Route($path, $method, $parameters, $name);
        $this->router->route($route, $name);
        $this->controllers->register($controller_name, $controller);
        return $route;
    }

    public function resource($name, $controller = NULL, array $parameters = array(), $singular = false)
    {
        $controller_name = $this->controllers->getNextName();
        $parameters['controller'] = $controller_name;
        $controller = $controller ? : $name;
        $this->controllers->register($controller_name, $controller);
        return $this->router->resource($name, $parameters, $singular);
    }

    public function root($controller, array $parameters = array())
    {
        $parameters['controller'] = $controller;
        $this->router->root($parameters);
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
        if (isset($this['default_timezone'])) {
            date_default_timezone_set($this['default_timezone']);
        }
        $request = $this->request;
        $this->log->write(sprintf('Request: [%s] %s Source: %s', $request->method, $request->path, $request->ip));
        $response = $this->dispatcher->dispatch($request);
        $this->log->write('Response: ' . $response->getStatus());
        $response->send();
    }

}
