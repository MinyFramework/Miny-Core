<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/Resources.php';
require_once dirname(__FILE__) . '/../../Routing/Route.php';

class ResourcesTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new Resources('resources');
    }

    public function singularizeProvider()
    {
        return array(
            array('apple', 'apples'),
            array('apple', 'apple'),
        );
    }

    /**
     * @dataProvider singularizeProvider
     */
    public function testSingularizeMethod($expected, $name)
    {
        $this->assertEquals($expected, Resources::singularize($name));
    }

    public function testResourcesShouldAlsoHaveSingularizedName()
    {
        $this->assertEquals('resource', $this->object->getSingularName());
    }

    public function testResourcesShouldNotHaveParentByDefault()
    {
        $this->assertFalse($this->object->hasParent());
    }

    public function testResourcesShouldReturnEmptyPathBaseIfItHasNoParent()
    {
        $this->assertEmpty($this->object->getPathBase());
    }

    public function testResourcesShouldCreate7RoutesByDefault()
    {
        $routes_iterator = $this->object->getIterator();
        $this->assertEquals(7, $routes_iterator->count());
        $expected_paths_and_methods = array(
            array('resource/:id', 'GET'),
            array('resource/:id', 'DELETE'),
            array('resource/:id/edit', 'GET'),
            array('resource/:id', 'PUT'),
            array('resources', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
        );
        $actual_paths_and_methods = array();
        foreach ($routes_iterator as $route) {
            $actual_paths_and_methods[] = array($route->getPath(), $route->getMethod());
        }
        $this->assertEquals($expected_paths_and_methods, $actual_paths_and_methods);
    }

    public function testResourcesShouldCreateRoutesSpecifiedInOnly()
    {
        $this->object->only('index', 'update');
        $routes_iterator = $this->object->getIterator();
        $this->assertEquals(2, $routes_iterator->count());
        $expected_paths_and_methods = array(
            array('resource/:id', 'PUT'),
            array('resources', 'GET')
        );
        $actual_paths_and_methods = array();
        foreach ($routes_iterator as $route) {
            $actual_paths_and_methods[] = array($route->getPath(), $route->getMethod());
        }
        $this->assertEquals($expected_paths_and_methods, $actual_paths_and_methods);
    }

    public function testResourcesShouldNotCreateRoutesSpecifiedInExcept()
    {
        $this->object->except('index', 'update');
        $routes_iterator = $this->object->getIterator();
        $this->assertEquals(5, $routes_iterator->count());
        $expected_paths_and_methods = array(
            array('resource/:id', 'GET'),
            array('resource/:id', 'DELETE'),
            array('resource/:id/edit', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
        );
        $actual_paths_and_methods = array();
        foreach ($routes_iterator as $route) {
            $actual_paths_and_methods[] = array($route->getPath(), $route->getMethod());
        }
        $this->assertEquals($expected_paths_and_methods, $actual_paths_and_methods);
    }

}

?>
