<?php

namespace Miny\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Router
     */
    private $router;

    public function setUp()
    {
        $parserMock = $this->getMock('\\Miny\\Router\\AbstractRouteParser');

        $parserMock->expects($this->any())
            ->method('parse')
            ->will(
                $this->returnCallback(
                    function ($path, $method) {
                        $route = new Route();

                        $route->setPath($path);
                        $route->setMethod($method);

                        if ($path === 'dynamic route') {
                            $route->setRegexp($path);
                        }

                        return $route;
                    }
                )
            );

        $this->router = new Router($parserMock);
    }

    public function testThatEmptyRouterIsEmpty()
    {
        $this->assertEmpty($this->router->getAll());
    }

    public function testRouterShouldReturnRouteAndResourceObjects()
    {
        $this->assertInstanceOf('\\Miny\\Router\\Route', $this->router->root());
        $this->assertInstanceOf('\\Miny\\Router\\Route', $this->router->add(''));
        $this->assertInstanceOf('\\Miny\\Router\\Route', $this->router->get(''));
        $this->assertInstanceOf('\\Miny\\Router\\Route', $this->router->post(''));
        $this->assertInstanceOf('\\Miny\\Router\\Route', $this->router->put(''));
        $this->assertInstanceOf('\\Miny\\Router\\Route', $this->router->delete(''));
        $this->assertInstanceOf('\\Miny\\Router\\Resource', $this->router->resource(''));
    }

    public function testThatRootCreatesAnEmptyRouteWithMethodGet()
    {
        $route = $this->router->root();

        $this->assertTrue($route->isMethod(Route::METHOD_GET));
        $this->assertEquals('', $route->getPath());
    }


    public function testThatAddCreatesARouteWithAllMethods()
    {
        $route = $this->router->add('');

        $this->assertTrue($route->isMethod(Route::METHOD_ALL));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatSetPrefixThrowsExceptionWhenParameterIsNotString()
    {
        $this->router->setPrefix(5);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatSetPostfixThrowsExceptionWhenParameterIsNotString()
    {
        $this->router->setPostfix(5);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatAddThrowsExceptionWhenParameterIsNotStringOrIntOrNull()
    {
        $this->router->add('', Route::METHOD_ALL, 5.2);
    }

    public function testThatOnlyPrefixIsAppliedToRoot()
    {
        $this->router->setPrefix('prefix/');
        $this->router->setPostfix('/postfix');

        $route = $this->router->root();
        $this->assertEquals('prefix/', $route->getPath());
    }

    public function testThatPrefixAndPostfixIsNotAppliedToAdd()
    {
        $this->router->setPrefix('prefix/');
        $this->router->setPostfix('/postfix');

        $route = $this->router->add('path');
        $this->assertEquals('path', $route->getPath());
    }

    public function testThatPrefixAndPostfixIsAppliedToGetPostPutAndDelete()
    {
        $this->router->setPrefix('prefix/');
        $this->router->setPostfix('/postfix');

        $get    = $this->router->get('path');
        $post   = $this->router->post('path');
        $put    = $this->router->put('path');
        $delete = $this->router->delete('path');

        $this->assertEquals('prefix/path/postfix', $get->getPath());
        $this->assertEquals('prefix/path/postfix', $post->getPath());
        $this->assertEquals('prefix/path/postfix', $put->getPath());
        $this->assertEquals('prefix/path/postfix', $delete->getPath());
    }

    public function testHas()
    {
        $this->router->add('', Route::METHOD_GET);
        $this->router->add('', Route::METHOD_GET, 'named');

        $this->assertFalse($this->router->has('nonexistent named'));
        $this->assertFalse($this->router->has(2));
        $this->assertTrue($this->router->has(0));
        $this->assertTrue($this->router->has('named'));
    }

    public function testHasStatic()
    {
        $this->router->add('path', Route::METHOD_GET);
        $this->router->add('dynamic route', Route::METHOD_GET);

        $this->assertTrue($this->router->hasStatic('path'));
        $this->assertFalse($this->router->hasStatic('dynamic route'));
    }

    public function testThatGlobalValuesAreAdded()
    {
        $array = array(
            'key_a' => 'value_a',
            'key_b' => 'value_b'
        );
        $this->router->addGlobalValues($array);

        $route = $this->router->add('');
        $this->assertEquals($array, $route->getDefaultValues());
    }

    public function testGetters()
    {
        $static   = $this->router->add('path', Route::METHOD_GET, 'static');
        $dynamic  = $this->router->add('dynamic route', Route::METHOD_GET, 'dynamic1');
        $dynamic2 = $this->router->add('dynamic route', Route::METHOD_GET, 'dynamic2');

        $this->assertSame($static, $this->router->getRoute('static'));
        $this->assertSame($static, $this->router->getStaticByURI('path'));

        $this->assertSame($dynamic, $this->router->getRoute('dynamic1'));
        $this->assertSame($dynamic2, $this->router->getRoute('dynamic2'));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testThatGetRouteThrowsExceptionWhenRouteIsNotFound()
    {
        $this->router->getRoute('route');
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testThatGetStaticByURIThrowsExceptionWhenRouteIsNotFound()
    {
        $this->router->getStaticByURI('route');
    }
}
