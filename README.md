The Miny PHP framework
=============
Miny is a small PHP framework. It is constantly under development and not recommended for production use.
Miny is developed as a microframework but its abilities can be extended by powerful modules.

Miny is licensed under the MIT License.

Requirements
=============

 * Any supported version of PHP (5.3.3 or newer)
 * Composer

Installation, basic usage
=============
An empty application template is available [here](http://github.com/MinyFramework/AppSkeleton)

If you wish to use the default logging options, create a writable directory called `/logs`.

Environments
=============
There are three separate environments available: development and production. The current environment
can be set via the Application class' constructor and determines which configuration files should be
used. By default the environment setting also determines if debug-level logging is enabled.

The default environment is the production environment.

Defining the environment to be used
----------
When instantiating the Application class, pass the desired constant as the first argument of the constructor.

```php
$app = new Application(Application::ENV_DEV);
```

The available constants are:

 * `Application::ENV_DEV` - development environment
 * `Application::ENV_TEST` - testing environment
 * `Application::ENV_PROD` - production environment

Configuration
=============
Configuration files are pure PHP files containing an array of configuration options.
Configuration files are located in the `/config` directory of the application.

Four different configuration files can be defined:

 * `config.common.php` for all environments
 * `config.dev.php` will be used only for the development environment
 * `config.test.php` will be loaded for the test environment
 * `config.php` for the production environment

`config.common.php` contains the baseline configuration. Setting a value here will act as a default
that can be overridden by the environment-specific configuration files.

Services
=============

Using the Inversion of Control container
-------------
Miny comes with a powerful Inversion of Control (IoC) container called `Container`. It can be used to
create object instances with their dependencies automatically injected.

### Obtaining the Container ###
The Container instance can be obtained by calling `$app->getContainer()`.

### Basic usage ###
Instantiating a class is as easy as calling the `get` method and passing the class name.

```php
$instance = $container->get('\\Namespace\\ClassName');
```

Why is this any good? For the simplest of use cases, this method only generates a lot of overhead.
However when a class has multiple dependencies on other objects not yet instantiated, `Container` will take
care of tracking down all the necessary data needed to create the class.

TBD
