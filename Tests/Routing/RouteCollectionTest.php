<?php

namespace Miny\Routing;

use OutOfBoundsException;

class RouteCollectionTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new RouteCollection;
        $this->object->addRoute(new Route('route_path'));
        $this->object->addRoute(new Route('some_other_path'), 'some_name');
    }

    public function testAddRoutes()
    {
        $other = new RouteCollection;
        $this->assertEquals(0, $other->getIterator()->count());

        $other->addRoute(new Route('path'));

        $this->assertEquals(1, $other->getIterator()->count());

        $this->assertFalse($other->hasRoute('some_name'));
        $other->addRoute(new Route('some_path'), 'some_name');
        $this->assertTrue($other->hasRoute('some_name'));

        $this->assertEquals(2, $other->getIterator()->count());
        return $other;
    }

    public function testGetRoutes()
    {
        $routes = $this->object->getRoutes();
        $this->assertCount(2, $routes);
        $this->assertContainsOnlyInstancesOf('\Miny\Routing\Route', $routes);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedException Parameter "name" must be string, integer or NULL.
     */
    public function testAddRouteInvalidName()
    {
        $this->object->addRoute(new Route('path'), array());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedException Parameter "name" must be a string.
     */
    public function testGetRouteNonStringName()
    {
        $this->object->getRoute(53);
    }

    /**
     * @depends testAddRoutes
     */
    public function testCollection(RouteCollection $other)
    {
        $this->object->merge($other);
        $this->assertEquals(3, $this->object->getIterator()->count());
        $this->assertEquals('some_path', $this->object->getRoute('some_name')->getPath());
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Route not found: nonexistent
     */
    public function testGetNonExistentRoute()
    {
        $this->object->getRoute('nonexistent');
    }
}

?>
