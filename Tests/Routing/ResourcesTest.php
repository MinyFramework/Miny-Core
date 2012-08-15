<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/Resources.php';
require_once dirname(__FILE__) . '/../../Routing/Route.php';

class ResourcesTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    protected $sub_resources;

    protected function setUp()
    {
        $this->object = new Resources('resources');
        $this->sub_resources = new Resources('sub_resources');
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
        $this->object->resource($this->sub_resources);
        $this->assertEquals('resource', $this->object->getSingularName());
        $this->assertEquals('resources_sub_resource', $this->sub_resources->getSingularName());
    }

    public function testResourcesShouldHavePathBase()
    {
        $this->assertEquals('resources', $this->object->getPathBase());
        $this->object->resource($this->sub_resources);
        $this->assertEquals('resources', $this->object->getPathBase());
        $this->assertEquals('resources/:resource_id/sub_resources', $this->sub_resources->getPathBase());
    }

    public function testResourcesShouldCreate7RoutesByDefault()
    {
        $expected_paths_and_methods = array(
            array('resources', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
            array('resources/:id', 'GET'),
            array('resources/:id', 'DELETE'),
            array('resources/:id/edit', 'GET'),
            array('resources/:id', 'PUT'),
        );
        $this->checkGeneratedRoutes($this->object, 7, $expected_paths_and_methods);
    }

    public function testChildResourcesShouldHaveParentsPathInThem()
    {
        $this->object->resource($this->sub_resources);
        $expected_paths_and_methods_for_sub_resources = array(
            array('resources/:resource_id/sub_resources', 'GET'),
            array('resources/:resource_id/sub_resources/new', 'GET'),
            array('resources/:resource_id/sub_resources', 'POST'),
            array('resources/:resource_id/sub_resources/:id', 'GET'),
            array('resources/:resource_id/sub_resources/:id', 'DELETE'),
            array('resources/:resource_id/sub_resources/:id/edit', 'GET'),
            array('resources/:resource_id/sub_resources/:id', 'PUT'),
        );
        $this->checkGeneratedRoutes($this->sub_resources, 7, $expected_paths_and_methods_for_sub_resources);
    }

    public function testParentResourcesShouldHaveChildsRoutesInThem()
    {
        $this->object->resource($this->sub_resources);
        $expected_paths_and_methods_with_parent = array(
            array('resources', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
            array('resources/:id', 'GET'),
            array('resources/:id', 'DELETE'),
            array('resources/:id/edit', 'GET'),
            array('resources/:id', 'PUT'),
            array('resources/:resource_id/sub_resources', 'GET'),
            array('resources/:resource_id/sub_resources/new', 'GET'),
            array('resources/:resource_id/sub_resources', 'POST'),
            array('resources/:resource_id/sub_resources/:id', 'GET'),
            array('resources/:resource_id/sub_resources/:id', 'DELETE'),
            array('resources/:resource_id/sub_resources/:id/edit', 'GET'),
            array('resources/:resource_id/sub_resources/:id', 'PUT'),
        );
        $this->checkGeneratedRoutes($this->object, 14, $expected_paths_and_methods_with_parent);
    }

    public function testResourcesPathShouldNotContainIdParameterForParentWhenItIsSingular()
    {
        $resource = new Resource('singular_resource');
        $resource->resource($this->object);
        $expected_paths_and_methods = array(
            array('singular_resource/resources', 'GET'),
            array('singular_resource/resources/new', 'GET'),
            array('singular_resource/resources', 'POST'),
            array('singular_resource/resources/:id', 'GET'),
            array('singular_resource/resources/:id', 'DELETE'),
            array('singular_resource/resources/:id/edit', 'GET'),
            array('singular_resource/resources/:id', 'PUT'),
        );
        $this->checkGeneratedRoutes($this->object, 7, $expected_paths_and_methods);
    }

    public function testResourcesShouldCreateRoutesSpecifiedInOnly()
    {
        $this->object->only('index', 'update');
        $expected_paths_and_methods = array(
            array('resources', 'GET'),
            array('resources/:id', 'PUT'),
        );
        $this->checkGeneratedRoutes($this->object, 2, $expected_paths_and_methods);
    }

    public function testResourcesShouldNotCreateRoutesSpecifiedInExcept()
    {
        $this->object->except('index', 'update');
        $expected_paths_and_methods = array(
            array('resources/new', 'GET'),
            array('resources', 'POST'),
            array('resources/:id', 'GET'),
            array('resources/:id', 'DELETE'),
            array('resources/:id/edit', 'GET'),
        );
        $this->checkGeneratedRoutes($this->object, 5, $expected_paths_and_methods);
    }

    public function testResourcesShouldCreateRoutesSpecifiedWithMember()
    {
        $this->object->member('GET', 'get_member_method');
        $expected_paths_and_methods = array(
            array('resources', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
            array('resources/:id', 'GET'),
            array('resources/:id', 'DELETE'),
            array('resources/:id/edit', 'GET'),
            array('resources/:id', 'PUT'),
            array('resources/:id/get_member_method', 'GET'),
        );
        $this->checkGeneratedRoutes($this->object, 8, $expected_paths_and_methods);
    }

    public function testResourcesShouldCreateRoutesSpecifiedWithCollection()
    {
        $this->object->collection('GET', 'get_collection_method');
        $expected_paths_and_methods = array(
            array('resources', 'GET'),
            array('resources/new', 'GET'),
            array('resources', 'POST'),
            array('resources/get_collection_method', 'GET'),
            array('resources/:id', 'GET'),
            array('resources/:id', 'DELETE'),
            array('resources/:id/edit', 'GET'),
            array('resources/:id', 'PUT'),
        );
        $this->checkGeneratedRoutes($this->object, 8, $expected_paths_and_methods);
    }

    private function checkGeneratedRoutes(Resources $resources, $expected_count, array $expected_paths_and_methods)
    {
        $routes_iterator = $resources->getIterator();
        $this->assertEquals($expected_count, $routes_iterator->count());
        $actual_paths_and_methods = array();
        foreach ($routes_iterator as $route) {
            $actual_paths_and_methods[] = array($route->getPath(), $route->getMethod());
        }
        $this->assertEquals($expected_paths_and_methods, $actual_paths_and_methods);
    }

}

?>
