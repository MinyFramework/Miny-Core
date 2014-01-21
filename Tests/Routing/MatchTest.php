<?php

namespace Miny\Routing;

class MatchTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    protected $route;
    protected $parameters;

    protected function setUp()
    {
        $this->parameters = array(
            'foo' => 'bar',
            'baz' => 'foobar'
        );
        $this->route      = new Route('test/path', NULL, $this->parameters);
        $this->object     = new Match($this->route, array('foo' => 'bar_baz'));
    }

    public function testGetParameters()
    {
        $this->assertEquals($this->parameters, $this->object->getParameters());
    }

    public function testGetRoute()
    {
        $this->assertSame($this->route, $this->object->getRoute());
    }
}

?>
