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
/*
 * App modulok: Module.php, Module class kiterjesztése
 * __construct: autoloader bizergálása
 *
 * Components
 *  - Application
 *  - Cache
 *  - ...
 * Modules
 *  - News
 *     - Module.php
 *     - Controllers
 *     - Models
 *     - Forms
 *     - ...
 */

namespace Miny\Application;

use \Miny\Factory\Factory;
use \Miny\Routing\Route;
use \Miny\HTTP\Request;

class Application extends Factory
{
    const ENV_PROD = 0;
    const ENV_DEV = 1;

    private $directory;
    private $modules = array();
    private $environment;

    public function __construct($directory, $environment = self::ENV_PROD)
    {
        $this->directory = $directory;
        $this->environment = $environment;

        if (is_file($directory . '/config/config.common.php')) {
            $config = include $directory . '/config/config.common.php';
        } else {
            $config = array();
        }
        parent::__construct($config);
        if ($environment == self::ENV_DEV && is_file($directory . '/config/config.dev.php')) {
            $config_file = $directory . '/config/config.dev.php';
        } else {
            $config_file = $directory . '/config/config.php';
        }

        $env_config = include $config_file;
        $this->setParameters($env_config);

        if (isset($this['default_timezone'])) {
            date_default_timezone_set($this['default_timezone']);
        }
        $this->registerDefaultServices();
        if (is_file($directory . '/config/routes.php')) {
            $router = $this->router;
            include $directory . '/config/routes.php';
        }
        if (is_file($directory . '/config/services.php')) {
            $factory = $this;
            include $directory . '/config/services.php';
        }
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
        $class = '\Modules\\' . ucfirst($module) . '\Module';
        $module_class = new $class;
        if (!$module_class instanceof Module) {
            throw new \UnexpectedValueException('Module descriptor should extend Module class.');
        }
        $this->modules[$module] = $module_class;
        foreach (array_keys($module_class->getDependencies()) as $name) {
            $this->module($name);
        }
        $module_class->init($this);
    }

    private function registerDefaultServices()
    {
        $this->add('events', '\Miny\Event\EventDispatcher');
        $this->add('validator', '\Miny\Validator\Validator');
        $this->add('form_validator', '\Miny\Form\FormValidator');

        $this->add('token_storage', '\Miny\Form\SessionTokenStorage')->setArguments('&session');
        $this->add('templating', '\Miny\Template\Template')->setArguments('@template_dir');
        $this->add('resolver', '\Miny\Controller\ControllerResolver')->setArguments('&templating');
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

    public function route($path, $controller, $name = NULL, $method = 'GET', array $parameters = array())
    {
        $method = strtolower($method);
        $controller_name = $this->resolver->getNextName();
        $parameters['controller'] = $controller_name;
        if (!in_array($method, array(NULL, 'GET', 'POST', 'PUT', 'DELETE'))) {
            throw new \UnexpectedValueException('Unexpected route method:' . $method);
        }
        $route = new Route($path, $method, $parameters);
        $this->router->route($route, $name);
        $this->resolver->register($controller_name, $controller);
        return $route;
    }

    public function run()
    {
        $this->dispatcher->dispatch($this->request)->send();
    }

}