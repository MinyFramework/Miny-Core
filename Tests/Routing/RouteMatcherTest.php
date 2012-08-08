<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/Match.php';
require_once dirname(__FILE__) . '/../../Routing/Route.php';
require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/RouteMatcher.php';

class RouteMatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $route;
    protected $static_route;
    protected $static_route_post;
    protected $object;

    protected function setUp()
    {
        $collection = new RouteCollection;
        $this->static_route = new Route('static_path');
        $this->static_route_post = new Route('other_static_path', 'POST');
        $this->route = new Route('path/:parameter', 'POST');
        $this->route->specify('parameter', '(\d+)');
        $collection->addRoute($this->route);
        $collection->addRoute($this->static_route);
        $collection->addRoute($this->static_route_post);
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
        //match NULL method
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $this->object->match('path/5'));
        //match specific method
        $match = $this->object->match('path/5', 'POST');
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $match);
        //check if match has the expected route
        $this->assertSame($this->route, $match->getRoute());
        $this->assertEquals(array('parameter' => 5), $match->getParameters());
    }

    public function testMatchStaticRoute()
    {
        //should match any method
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $this->object->match('static_path'));
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $this->object->match('static_path', 'any_method'));
        //should only match POST (or NULL)
        $this->assertFalse($this->object->match('other_static_path', 'any_method_except_post'));
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $this->object->match('other_static_path'));
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $this->object->match('other_static_path', 'POST'));
    }

}

?>
