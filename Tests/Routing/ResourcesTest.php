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
        $expected_paths_and_methods = array(
            array('resource/:id', 'GET'),
            array('resource/:id', 'DELETE'),
            array('resource/:id/edit', 'GET'),
            array('resource/:id', 'PUT'),
            array('resources', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
        );
        $this->checkGeneratedRoutes(7, $expected_paths_and_methods);
    }

    public function testResourcesShouldCreateRoutesSpecifiedInOnly()
    {
        $this->object->only('index', 'update');
        $expected_paths_and_methods = array(
            array('resource/:id', 'PUT'),
            array('resources', 'GET')
        );
        $this->checkGeneratedRoutes(2, $expected_paths_and_methods);
    }

    public function testResourcesShouldNotCreateRoutesSpecifiedInExcept()
    {
        $this->object->except('index', 'update');
        $expected_paths_and_methods = array(
            array('resource/:id', 'GET'),
            array('resource/:id', 'DELETE'),
            array('resource/:id/edit', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
        );
        $this->checkGeneratedRoutes(5, $expected_paths_and_methods);
    }

    public function testResourcesShouldCreateRoutesSpecifiedWithMember()
    {
        $this->object->member('GET', 'get_member_method');
        $expected_paths_and_methods = array(
            array('resource/:id', 'GET'),
            array('resource/:id', 'DELETE'),
            array('resource/:id/edit', 'GET'),
            array('resource/:id', 'PUT'),
            array('resource/:id/get_member_method', 'GET'),
            array('resources', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST')
        );
        $this->checkGeneratedRoutes(8, $expected_paths_and_methods);
    }

    public function testResourcesShouldCreateRoutesSpecifiedWithCollection()
    {
        $this->object->collection('GET', 'get_collection_method');
        $expected_paths_and_methods = array(
            array('resource/:id', 'GET'),
            array('resource/:id', 'DELETE'),
            array('resource/:id/edit', 'GET'),
            array('resource/:id', 'PUT'),
            array('resources', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
            array('resources/get_collection_method', 'GET'),
        );
        $this->checkGeneratedRoutes(8, $expected_paths_and_methods);
    }

    private function checkGeneratedRoutes($expected_count, array $expected_paths_and_methods)
    {
        $routes_iterator = $this->object->getIterator();
        $this->assertEquals($expected_count, $routes_iterator->count());
        $actual_paths_and_methods = array();
        foreach ($routes_iterator as $route) {
            $actual_paths_and_methods[] = array($route->getPath(), $route->getMethod());
        }
        $this->assertEquals($expected_paths_and_methods, $actual_paths_and_methods);
    }

}

?>
