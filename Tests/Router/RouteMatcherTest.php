<?php

namespace Miny\Router;

class RouteMatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteMatcher
     */
    private $matcher;
    private $routerMock;

    public function setUp()
    {
        $this->routerMock = $this->getMockBuilder('\\Miny\\Router\\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher = new RouteMatcher($this->routerMock);
    }

    public function testThatMatcherReturnsFalseWhenNoMatchIsFound()
    {
        $this->routerMock->expects($this->any())
            ->method('getAll')
            ->will($this->returnValue(array()));

        $this->assertFalse($this->matcher->match('some path'));
    }

    public function testThatMatcherReturnsMatchInstance()
    {
        $staticGetRoute = new Route();
        $staticGetRoute->setPath('static path');
        $staticGetRoute->setMethod(Route::METHOD_GET);

        $this->routerMock->expects($this->any())
            ->method('hasStatic')
            ->will($this->returnValue(true));

        $this->routerMock->expects($this->any())
            ->method('getStaticByURI')
            ->will($this->returnValue($staticGetRoute));

        $this->assertInstanceOf('\\Miny\\Router\\Match', $this->matcher->match('static path'));
    }

    public function testThatMatcherReturnsFalseWhenRouteMethodDoesNotMatch()
    {
        $this->routerMock->expects($this->any())
            ->method('getAll')
            ->will($this->returnValue(array()));

        $staticGetRoute = new Route();
        $staticGetRoute->setPath('static path');
        $staticGetRoute->setMethod(Route::METHOD_GET);

        $this->routerMock->expects($this->any())
            ->method('hasStatic')
            ->will($this->returnValue(true));

        $this->routerMock->expects($this->any())
            ->method('getStaticByURI')
            ->will($this->returnValue($staticGetRoute));

        $this->assertFalse($this->matcher->match('static path', Route::METHOD_POST));
    }

    public function testThatMatcherMatchesDynamicRoutes()
    {
        $this->createDynamicRoutes();

        $this->assertFalse($this->matcher->match('path 5', Route::METHOD_POST));

        $match = $this->matcher->match('path 50 6', Route::METHOD_GET);
        $this->assertInstanceOf(
            '\\Miny\\Router\\Match',
            $match
        );
        $this->assertEquals(array('id' => 6), $match->getParameters());
    }

    public function testThatMatcherReturnsFalseWhenDynamicRoutesDoNotMatch()
    {
        $this->createDynamicRoutes();

        $this->assertFalse($this->matcher->match('path 50 name', Route::METHOD_GET));
    }

    /**
     * @return array
     */
    private function createDynamicRoutes()
    {
        $routes = array();

        for ($i = 0; $i < 100; $i++) {
            $route = new Route();

            $route->setPath('path ' . $i . ' {id}');
            $route->specify('id', '\d+');
            $route->setRegexp('path ' . $i . ' (\d+)');

            $route->setMethod(Route::METHOD_GET);

            $routes[] = $route;
        }

        $this->routerMock->expects($this->any())
            ->method('getAll')
            ->will(
                $this->returnValue(
                    $routes
                )
            );
    }
}