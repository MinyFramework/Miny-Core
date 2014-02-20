<?php

namespace Miny\Routing;

use InvalidArgumentException;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Route
     */
    protected $staticRoute;

    /**
     * @var Route
     */
    protected $dynamicRoute;

    protected function setUp()
    {
        $this->staticRoute  = new Route('path', 'get',
            array(
                'param_foo' => 'val_foo',
                'param_bar' => 'val_bar',
            ));
        $this->dynamicRoute = new Route('path/{field:\w+}', 'get',
            array(
                'param_foo' => 'val_foo',
                'param_bar' => 'val_bar',
            ));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Path must be a string
     */
    public function testConstructorPathException()
    {
        new Route(5);
    }

    /**
     * @expectedException \Miny\Routing\Exceptions\BadMethodException
     * @expectedExceptionMessage Unexpected route method: 5
     */
    public function testConstructorMethodException()
    {
        new Route('foo', 5);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Path must be a string
     */
    public function testSetPathException()
    {
        $this->staticRoute->setPath(5);
    }

    public function testPaths()
    {
        $this->assertEquals('path', $this->staticRoute->getPath());

        $this->staticRoute->setPath('path2');

        $this->assertEquals('path2', $this->staticRoute->getPath());
    }

    public function testGetMethod()
    {
        $this->assertEquals('GET', $this->staticRoute->getMethod());
    }

    public function testRegex()
    {
        $this->assertEquals('path/(\w+)', $this->dynamicRoute->getRegex());
        $this->assertEquals($this->staticRoute->getPath(), $this->staticRoute->getRegex());
    }

    public function testPatterns()
    {
        $this->dynamicRoute->specify('field', '(.*?)');
        $this->assertEquals('(.*?)', $this->dynamicRoute->getPattern('field'));
        $this->assertEquals('([^/]+)', $this->dynamicRoute->getPattern('nonexistent'));
        $this->assertEquals('path/(.*?)', $this->dynamicRoute->getRegex());
    }

    public function testDynamic()
    {
        $this->assertEquals(array('field'), $this->dynamicRoute->getParameterNames());
        $this->assertEquals(1, $this->dynamicRoute->getParameterCount());
        $this->assertFalse($this->dynamicRoute->isStatic());
    }

    public function testStatic()
    {
        $this->assertEquals(array(), $this->staticRoute->getParameterNames());
        $this->assertEquals(0, $this->staticRoute->getParameterCount());
        $this->assertTrue($this->staticRoute->isStatic());
    }

    public function testParameters()
    {
        $this->assertEquals(
            array(
                'param_foo' => 'val_foo',
                'param_bar' => 'val_bar',
            ),
            $this->staticRoute->getParameters()
        );

        $this->staticRoute->addParameters(
            array(
                'param_foobar' => 'val_foobar',
                'param_bar'    => 'some_other'
            )
        );
        $this->assertEquals(
            array(
                'param_foo'    => 'val_foo',
                'param_bar'    => 'some_other',
                'param_foobar' => 'val_foobar',
            ),
            $this->staticRoute->getParameters()
        );
    }
}

?>
