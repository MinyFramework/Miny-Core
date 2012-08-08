<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/Match.php';
require_once dirname(__FILE__) . '/../../Routing/Route.php';
require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/RouteMatcher.php';

class RouteMatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $route;
    protected $object;

    protected function setUp()
    {
        $collection = new RouteCollection;
        $this->route = new Route('path/:parameter', 'POST');
        $this->route->specify('parameter', '(\d+)');
        $collection->addRoute($this->route);
        $this->object = new RouteMatcher($collection);
    }

    public function testMatch()
    {
        //path not found
        $this->assertFalse($this->object->match('path'));
        //wrong method
        $this->assertFalse($this->object->match('path/5', 'PUT'));
        //wrong pattern
        $this->assertFalse($this->object->match('path/foo'));
        //match
        $match = $this->object->match('path/5');
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $match);
        $this->assertSame($this->route, $match->getRoute());
        $this->assertEquals(array('parameter' => 5), $match->getParameters());
    }

}

?>
