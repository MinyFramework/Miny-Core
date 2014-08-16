<?php

namespace Miny\Router;

class RouteMatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteMatcher
     */
    private $matcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
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
            ->will($this->returnValue([]));

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
            ->will($this->returnValue([]));

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


    public function testThatMatcherMatchesAFewDynamicRoutes()
    {
        $this->createDynamicRoutes(5);

        $this->assertFalse($this->matcher->match('path 3', Route::METHOD_POST));

        $match = $this->matcher->match('path 2 6', Route::METHOD_GET);
        $this->assertInstanceOf(
            '\\Miny\\Router\\Match',
            $match
        );
        $this->assertEquals(['id' => 6], $match->getParameters());
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
        $this->assertEquals(['id' => 6], $match->getParameters());
    }

    public function testThatMatcherReturnsFalseWhenDynamicRoutesDoNotMatch()
    {
        $this->createDynamicRoutes();

        $this->assertFalse($this->matcher->match('path 50 name', Route::METHOD_GET));
    }

    private function createDynamicRoutes($num = 100)
    {
        $routes = [];

        $route = new Route();

        $route->setPath('path 0 {id} {other}');
        $route->specify('id', '\d+');
        $route->specify('other', '\d+');
        $route->setRegexp('path 0 (\d+) (\d+)');

        $route->setMethod(Route::METHOD_GET);

        $routes[] = $route;
        for ($i = 1; $i < $num; $i++) {
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
