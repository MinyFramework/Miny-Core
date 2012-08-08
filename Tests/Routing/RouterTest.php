<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/Router.php';

class RouterTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new Router;
    }

    public function testSimpleRoutes()
    {
        $this->markTestIncomplete('Missing test.');
    }

}

?>
