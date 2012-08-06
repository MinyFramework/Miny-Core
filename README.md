The Miny PHP framework
=============
Miny is a small PHP framework. It is constantly under development and not recommended for production use.

Miny is licensed under the MIT License.

[Project webpage](http://projectminy.bugadani.hu/en/index.html)

Requires
----------
PHP 5.3

Basic usage:
----------

```php
<?php
include __DIR__ . '/../Miny/Core/Application/Application.php';

$app = new Miny\Application\Application(__DIR__);
$app->root(function() {
    echo 'Hello, world!';
});
$app->run();

```

This is the simplest application for Miny. It prints Hello, world! for the root level route.

Documentation
------------
This is not a full API documentation. Full docs coming later.

### Application
Application extends Factory to be able to conviniently use services.
#### Methods

 * `__construct($directory, $environment = self::ENV_PROD, $include_configs = true)`:
 * `loadConfig($file, $env = self::ENV_COMMON)`: loads a configuration file if the environment matches.
 * `route($path, $controller, $method = NULL, $name = NULL, array $parameters = array())`
 * `root()`, `get()`, `post()`, `put()`, `delete()`: Shorthand methods for `route()`. Argument list is identical except `$method` is missing.
 * `run()`: Executes the application.
 * `module($module)`: Loads a module.
 * array operators: access configuration parameters (See Factory)
 * magic properties: set or access services (See Factory)

#### Default services
 * Router
 * View
 * Events
 * Dispatcher
 * Validator
 * ControllerCollection (controllers)
 * ControllerResolver (resolver)
 * Session
 * Log

#### Default configuration values

For syntax explanation, see corresponding section of Factory. `$directory` is the first argument given to `Application::__construct()`

```php
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

```

 * `default_timezone`: specifies the timezone to be used.
 * `root`: the root directory of the application
 * `log_path`: the folder for log files
 * `view`
    * `dir`: the directory where the template files are
    * `default_format`: default format for the templates
    * `exception`: the template to render when an exception occurs
 * `router`
    * `prefix`: this string is prepended to all routes by default
    * `suffix`: this string is appended to all routes except root by default
    * `defaults`: the default route parameters
    * `exception_paths`: Classname => route values. If an exception occurs during request filtering and the exception is in this array, the request filtering process restarts with the corresponding route.