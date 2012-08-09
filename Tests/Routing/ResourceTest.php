<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/Resources.php';
require_once dirname(__FILE__) . '/../../Routing/Resource.php';
require_once dirname(__FILE__) . '/../../Routing/Route.php';

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new Resource('foo');
    }

    public function testResourceNameShouldBeItsSingularName()
    {
        $this->assertEquals($this->object->getName(), $this->object->getSingularName());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testShouldNotBeAbleToCallMember()
    {
        $this->object->member('foo', 'bar');
    }

    public function testShouldGenerateMethods()
    {
        $expected_paths_and_methods = array(
            array('foo', 'GET'),
            array('foo', 'DELETE'),
            array('foo/edit', 'GET'),
            array('foo', 'PUT'),
            array('foo/new', 'GET'),
            array('foo', 'POST'),
        );
        $this->checkGeneratedRoutes($this->object, 6, $expected_paths_and_methods);
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
