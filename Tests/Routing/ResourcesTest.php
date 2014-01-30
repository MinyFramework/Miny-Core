<?php

namespace Miny\Routing;

class ResourcesTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    protected $sub_resources;

    protected function setUp()
    {
        $this->object        = new Resources('resources');
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

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Parameter "name" must be a string.
     */
    public function testConstructorException()
    {
        new Resources(5);
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

    public function testGetRoute()
    {
        $this->assertInstanceOf('Miny\Routing\Route', $this->object->getRoute('edit_resource'));
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Route not found: index
     */
    public function testNonExistingGetRoute()
    {
        $this->assertInstanceOf('Miny\Routing\Route', $this->object->getRoute('index'));
    }

    public function testSpecify()
    {
        $result = $this->object->specify('\d+');

        $this->assertSame($result, $this->object);
        $route = $this->object->getRoute('edit_resource');
        $this->assertEquals('\d+', $route->getPattern('id'));
    }

    public function testAddParameter()
    {
        $this->object->addParameter('param', 'value');

        $route  = $this->object->getRoute('edit_resource');
        $params = $route->getParameters();
        $this->assertArrayHasKey('param', $params);
        $this->assertEquals('value', $params['param']);
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Parameter "key" must be a string.
     */
    public function testAddParameterException()
    {
        $this->object->addParameter(5, 'value');
    }

    public function testAddParameters()
    {
        $this->object->addParameter('param', 'value');
        $this->object->addParameters(array(
            'foo'   => 'bar',
            'param' => 'override'
        ));

        $route  = $this->object->getRoute('edit_resource');
        $params = $route->getParameters();
        $this->assertArrayHasKey('foo', $params);
        $this->assertEquals('override', $params['param']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Pattern must be a string
     */
    public function testSpecifyException()
    {
        $this->object->specify(54);
    }
}

?>
