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

```

Documentation
------------
Later.