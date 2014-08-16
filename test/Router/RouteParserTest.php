<?php

namespace Miny\Router;

class RouteParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouteParser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new RouteParser();
    }

    public function testDefaultPatterns()
    {
        $this->assertEquals('[^/]+', $this->parser->getDefaultPattern());

        $parser = new RouteParser('\d+');
        $this->assertEquals('\d+', $parser->getDefaultPattern());
    }

    public function testThatStaticRoutesAreParsed()
    {
        $route = $this->parser->parse('/static/route');

        $this->assertTrue($route->isStatic());
        $this->assertEquals('/static/route', $route->getPath());
    }

    public function testThatDynamicRoutesAreParsedCorrectly()
    {
        $route = $this->parser->parse('/static/route/{id}');

        $this->assertFalse($route->isStatic());
        $this->assertEquals(1, $route->getParameterCount());
        $this->assertEquals(['id' => '[^/]+'], $route->getParameterPatterns());
        $this->assertEquals('/static/route/([^/]+)', $route->getRegexp());
    }

    public function testThatPatternsAreSetCorrectly()
    {
        $route = $this->parser->parse('/static/route/{id:\d+}');

        $this->assertFalse($route->isStatic());
        $this->assertEquals(1, $route->getParameterCount());
        $this->assertEquals(['id' => '\d+'], $route->getParameterPatterns());
        $this->assertEquals('/static/route/(\d+)', $route->getRegexp());
    }
}
