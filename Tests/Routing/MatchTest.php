<?php

namespace Miny\Routing;

class MatchTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    protected $route;

    protected function setUp()
    {
        $parameters   = array(
            'foo' => 'bar',
            'baz' => 'foobar'
        );
        $this->route  = new Route('test/path', null, $parameters);
        $this->object = new Match($this->route, array('foo' => 'bar_baz'));
    }

    public function testMatchedParametersShouldOverrideDefaults()
    {
        $expected = array(
            'foo' => 'bar_baz',
            'baz' => 'foobar'
        );
        $this->assertEquals($expected, $this->object->getParameters());
    }

    public function testGetRoute()
    {
        $this->assertSame($this->route, $this->object->getRoute());
    }
}

?>
