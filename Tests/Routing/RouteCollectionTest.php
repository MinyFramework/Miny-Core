<?php

namespace Miny\Routing;

use OutOfBoundsException;

require_once dirname(__FILE__) . '/../../Routing/Route.php';
require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';

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

        $other->addRoute(new Route('some_path'), 'some_name');

        $this->assertEquals(2, $other->getIterator()->count());
        return $other;
    }

    /**
     * @depends testAddRoutes
     */
    public function testCollection(RouteCollection $other)
    {
        $this->object->merge($other);
        $this->assertEquals(3, $this->object->getIterator()->count());
        $this->assertEquals('some_path', $this->object->getRoute('some_name')->getPath());
        try {
            $this->object->getRoute('nonexistent');
            $this->fail('Trying to retrieve a nonexistent route should throw an exception');
        } catch (OutOfBoundsException $e) {

        }
    }
}

?>
