<?php

namespace Miny\Routing;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new Router('prefix/', '.suffix',
            array(
                'default_parameter' => 'default_value',
                'parameter'         => 'value'
            ));
    }

    public function testShortUrls()
    {
        $this->assertTrue($this->object->shortUrls());
        $object = new Router(null, null, array(), false);
        $this->assertFalse($object->shortUrls());
    }

    public function testRootShouldOnlyHavePrefixByDefault()
    {
        $this->object->root(array('parameter' => 'other_value'));
        $this->assertEquals(
            'prefix/',
            $this->object->getRouteCollection()->getRoute('root')->getPath()
        );
        $this->assertEquals(
            array(
                'default_parameter' => 'default_value',
                'parameter'         => 'other_value'
            ),
            $this->object->getRouteCollection()->getRoute('root')->getParameters()
        );
    }

    public function testRootShouldOverwriteDefaultParameters()
    {
        $this->object->root(array('parameter' => 'other_value'));
        $this->assertEquals(
            array(
                'default_parameter' => 'default_value',
                'parameter'         => 'other_value'
            ),
            $this->object->getRouteCollection()->getRoute('root')->getParameters()
        );
    }

    public function testRoutesHaveBothPrefixAndSuffixByDefault()
    {
        $route = new Route('path');
        $this->object->route($route, 'name');
        $this->assertEquals(
            'prefix/path.suffix',
            $this->object->getRouteCollection()->getRoute('name')->getPath()
        );
    }

    public function testRouteShouldReturnTheGivenRoute()
    {
        $route = new Route('path');
        $this->assertSame($route, $this->object->route($route, 'name'));
    }

    public function testRouterShouldAllowDisablingPrefixAndSuffix()
    {
        $route = new Route('path');
        $this->object->route($route, 'name', false, false);
        $this->assertEquals(
            'path',
            $this->object->getRouteCollection()->getRoute('name')->getPath()
        );
    }

    public function testRouterShouldGeneratePathsWithPrefixAndSuffixByDefault()
    {
        $route = new Route('path/:param');
        $this->object->route($route, 'name');
        $this->assertEquals(
            'prefix/path/value.suffix',
            $this->object->generate('name', array('param' => 'value'))
        );
    }

    public function testRouterShouldMatchPathsWithPrefixAndSuffixByDefault()
    {
        $route = new Route('path/:param', null, array('parameter' => 'other_value'));
        $this->object->route($route, 'name');
        $match = $this->object->match('prefix/path/5.suffix');
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $match);
        $this->assertSame($route, $match->getRoute());
        $this->assertEquals(
            array(
                'default_parameter' => 'default_value',
                'parameter'         => 'other_value',
                'param'             => 5
            ),
            $match->getParameters()
        );
    }

    public function testRouterShouldBuildResourcesBeforeMatch()
    {
        $this->object->resources('foo_resources', array('parameter' => 'resource_parameter'));
        $match = $this->object->match('prefix/foo_resources/5.suffix', 'GET');
        $this->assertInstanceOf(__NAMESPACE__ . '\Match', $match);
        $this->assertEquals(
            array(
                'default_parameter' => 'default_value',
                'parameter'         => 'resource_parameter',
                'id'                => '5',
                'controller'        => 'foo_resources',
                'action'            => 'show'
            ),
            $match->getParameters()
        );
    }

    public function testRouterShouldBuildResourcesBeforeGenerate()
    {
        $this->object->resources('foo_resources', array('parameter' => 'resource_parameter'));
        $this->assertEquals(
            'prefix/foo_resources/5.suffix',
            $this->object->generate('foo_resource', array('id' => 5))
        );
    }

    public function testResource()
    {
        $this->assertInstanceOf('\Miny\Routing\Resource', $this->object->resource('foo'));
    }
}

?>
