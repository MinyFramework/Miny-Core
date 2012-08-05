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
use Miny\AutoLoader;
use Miny\Application\Application;
include __DIR__ . '/../Miny/Core/AutoLoader.php';
new AutoLoader();

$app = new Application(__DIR__);
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
#### Methods

 * `__construct($directory, $environment = self::ENV_PROD, $include_configs = true)`:
 * `loadConfig($file, $env = self::ENV_COMMON)`: loads a configuration file if the environment matches.
 * `route($path, $controller, $method = NULL, $name = NULL, array $parameters = array())`
 * `root()`, `get()`, `post()`, `put()`, `delete()`: Shorthand methods for `route()`.
 * `run()`: Executes the application.
 * `module($module)`: Loads a module.
 * array operators: access configuration parameters
 * magic properties: set or access services

#### Services
 * Router
 * View
 * Events
 * Dispatcher
 * Validator
 * ControllerCollection (controllers)
 * ControllerResolver (resolver)
 * Session
 * Log
