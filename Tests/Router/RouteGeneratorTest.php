<?php

namespace Miny\Router;

class RouteGeneratorTest extends \PHPUnit_Framework_TestCase
{
    private $routerMock;

    /**
     * @var RouteGenerator
     */
    private $generator;

    public function setUp()
    {
        $this->routerMock = $this->getMockBuilder('\\Miny\\Router\\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $route = new Route();
        $route->setPath('/some/path/{id}/{foo}/{bar}');
        $route->specify('id', '\d+');
        $route->specify('foo', '\w+');
        $route->specify('bar', '\w+');
        $route->set('bar', 'baz');

        $this->routerMock->expects($this->any())
            ->method('getRoute')
            ->will($this->returnValue($route));

        $this->generator = new RouteGenerator($this->routerMock);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatRouteGeneratorThrowsAnExceptionWhenParametersAreNotSet()
    {
        $this->generator->generate('route');
    }

    public function testThatDefaultValuesAreUsed()
    {
        $generated = $this->generator->generate(
            'route',
            array(
                'id'  => 1,
                'foo' => 'bar'
            )
        );

        $this->assertEquals('/some/path/1/bar/baz', $generated);
    }

    public function testThatDefaultValuesAreOverridden()
    {
        $generated = $this->generator->generate(
            'route',
            array(
                'id'  => 1,
                'foo' => 'bar',
                'bar' => 'foobar'
            )
        );

        $this->assertEquals('/some/path/1/bar/foobar', $generated);
    }

    public function testThatExtraValuesAreAppended()
    {
        $generated = $this->generator->generate(
            'route',
            array(
                'id'    => 1,
                'foo'   => 'bar',
                'bar'   => 'foobar',
                'extra' => 'foo',
                'baz'   => 'barbaz'
            )
        );

        $this->assertEquals('/some/path/1/bar/foobar?extra=foo&baz=barbaz', $generated);
    }

    public function testThatQueryStringIsCreatedWhenShortUrlsAreDisabled()
    {
        $generator = new RouteGenerator($this->routerMock, false);
        $generated = $generator->generate(
            'route',
            array(
                'id'    => 1,
                'foo'   => 'bar',
                'bar'   => 'foobar',
                'extra' => 'foo',
                'baz'   => 'barbaz'
            )
        );

        $this->assertEquals(
            '?path=%2Fsome%2Fpath%2F1%2Fbar%2Ffoobar&extra=foo&baz=barbaz',
            $generated
        );
    }
}
