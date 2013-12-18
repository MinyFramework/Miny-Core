<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/Route.php';

class RouteTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->static_object  = new Route('path', 'static_method',
                array(
            'param_foo' => 'val_foo',
            'param_bar' => 'val_bar',
        ));
        $this->dynamic_object = new Route('path/:field', 'some_method',
                array(
            'param_foo' => 'val_foo',
            'param_bar' => 'val_bar',
        ));
    }

    public function testPaths()
    {
        $this->assertEquals('path', $this->static_object->getPath());
        $this->assertEquals('path/:field', $this->dynamic_object->getPath());

        $this->static_object->setPath('path2');

        $this->assertEquals('path2', $this->static_object->getPath());
    }

    public function testGetMethod()
    {
        $this->assertEquals('static_method', $this->static_object->getMethod());
    }

    public function testRegex()
    {
        $this->assertEquals('path/(\w+)', $this->dynamic_object->getRegex());
        $this->assertNull($this->static_object->getRegex());
    }

    public function testPatterns()
    {
        $route = $this->dynamic_object;
        $route->specify('field', '(.*?)');
        $this->assertEquals('(.*?)', $route->getPattern('field'));
        $this->assertEquals('(\w+)', $route->getPattern('nonexistent'));
        $this->assertEquals('path/(.*?)', $route->getRegex());
    }

    public function testDynamic()
    {
        $route = $this->dynamic_object;
        $this->assertEquals(array('field'), $route->getParameterNames());
        $this->assertEquals(1, $route->getParameterCount());
        $this->assertFalse($route->isStatic());
    }

    public function testStatic()
    {
        $route = $this->static_object;
        $this->assertEquals(array(), $route->getParameterNames());
        $this->assertEquals(0, $route->getParameterCount());
        $this->assertTrue($route->isStatic());
    }

    public function testParameters()
    {
        $route = $this->static_object;
        $this->assertEquals(array(
            'param_foo' => 'val_foo',
            'param_bar' => 'val_bar',
                ), $route->getParameters());

        $route->addParameters(array(
            'param_foobar' => 'val_foobar',
            'param_bar'    => 'some_other'
        ));
        $this->assertEquals(array(
            'param_foo'    => 'val_foo',
            'param_bar'    => 'some_other',
            'param_foobar' => 'val_foobar',
                ), $route->getParameters());
    }
}

?>
