<?php

namespace Miny\Routing;

class RouteMatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $route;
    protected $static_route;
    protected $static_route_post;
    protected $object;

    protected function setUp()
    {
        $collection = new RouteCollection;

        $this->static_route      = new Route('static_path');
        $this->static_route_post = new Route('other_static_path', 'POST');
        $this->route             = new Route('path/{parameter:\d+}', 'POST');

        $collection->addRoute($this->route);
        $collection->addRoute($this->static_route);
        $collection->addRoute($this->static_route_post);

        $this->object = new RouteMatcher($collection);
    }

    public function testShouldNotMatchRouteWhenPathDoesNotMatch()
    {
        $this->assertFalse($this->object->match('path'));
    }

    public function testShouldNotMatchRouteWhenMethodDoesNotMatch()
    {
        $this->assertFalse($this->object->match('path/5', 'PUT'));
    }

    public function testShouldNotMatchRouteWhenParameterValueDoesNotMatchPattern()
    {
        $this->assertFalse($this->object->match('path/foo'));
    }

    public function testShouldMatchRouteWithMethodWhenMethodIsNotSuppliedToMatch()
    {
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $this->object->match('path/5'));
    }

    public function testShouldMatchRouteWhenTheSpecifiedMethodIsPassed()
    {
        $match = $this->object->match('path/5', 'POST');
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $match);
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
