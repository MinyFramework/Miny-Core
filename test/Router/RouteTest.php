<?php

namespace Miny\Router;

class RouteTest extends \PHPUnit_Framework_TestCase
{

    public function testThatEmptyRouteIsStatic()
    {
        $route = new Route();

        $this->assertTrue($route->isStatic());
        $this->assertEmpty($route->getParameterPatterns());
        $this->assertEquals(0, $route->getParameterCount());
    }

    public function testThatPathIsReturned()
    {
        $route = new Route();

        $path = 'path string';

        $route->setPath($path);
        $this->assertEquals($path, $route->getPath());
    }

    public function testThatDefaultValuesDoNotMakeARouteDynamic()
    {
        $route = new Route();

        $route->set(array('key' => 'value', 'key2' => 'value2'));
        $this->assertTrue($route->isStatic());

        return $route;
    }

    /**
     * @depends testThatDefaultValuesDoNotMakeARouteDynamic
     */
    public function testThatDefaultValuesAreReturned(Route $route)
    {
        $route->set('key3', 'value3');
        $this->assertEquals(
            array(
                'key'  => 'value',
                'key2' => 'value2',
                'key3' => 'value3'
            ),
            $route->getDefaultValues()
        );
    }

    /**
     * @expectedException \Miny\Router\Exceptions\BadMethodException
     */
    public function testThatInvalidMethodThrowsException()
    {
        $route = new Route();

        $route->setMethod(Route::METHOD_ALL + 1);
    }

    public function testMethods()
    {
        $route = new Route();

        $route->setMethod(Route::METHOD_GET | Route::METHOD_POST);
        $this->assertTrue($route->isMethod(Route::METHOD_ALL));
        $this->assertTrue($route->isMethod(Route::METHOD_GET));
        $this->assertTrue($route->isMethod(Route::METHOD_POST));
        $this->assertFalse($route->isMethod(Route::METHOD_DELETE));

        $route = new Route();
        $route->setMethod(Route::METHOD_ALL);

        $this->assertTrue($route->isMethod(Route::METHOD_ALL));
        $this->assertTrue($route->isMethod(Route::METHOD_GET));
        $this->assertTrue($route->isMethod(Route::METHOD_POST));
        $this->assertTrue($route->isMethod(Route::METHOD_PUT));
        $this->assertTrue($route->isMethod(Route::METHOD_DELETE));
    }

    public function testThatSpecifyDoesNotMakeARouteDynamic()
    {
        $route = new Route();

        $this->assertTrue($route->isStatic());
        $route->specify('name', 'pattern');
        $this->assertTrue($route->isStatic());

        return $route;
    }

    /**
     * @depends testThatSpecifyDoesNotMakeARouteDynamic
     */
    public function testThatPatternsAreReturned(Route $route)
    {
        $route->specify('another', 'other pattern');
        $route->specify('third name', 'third pattern');

        $this->assertEquals(3, $route->getParameterCount());
        $this->assertEquals(
            array('name', 'another', 'third name'),
            $route->getParameterNames()
        );
        $this->assertEquals(
            array(
                'name'       => 'pattern',
                'another'    => 'other pattern',
                'third name' => 'third pattern'
            ),
            $route->getParameterPatterns()
        );
    }

    public function testThatRegexpMakesRouteDynamic()
    {
        $route = new Route();

        $route->setRegexp('regexp');
        $this->assertFalse($route->isStatic());

        $this->assertEquals('regexp', $route->getRegexp());
    }
}
