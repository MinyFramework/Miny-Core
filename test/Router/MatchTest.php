<?php

namespace Miny\Router;

class MatchTest extends \PHPUnit_Framework_TestCase
{

    public function testThatRouteAndParamsAreReturned()
    {
        $route      = new Route();
        $parameters = [
            'name'  => 'value',
            'other' => 'other value'
        ];
        $route->set($parameters);

        $match = new Match($route);

        $this->assertSame($route, $match->getRoute());
        $this->assertEquals($parameters, $match->getParameters());

        return $route;
    }

    /**
     * @depends testThatRouteAndParamsAreReturned
     */
    public function testThatParametersOverrideDefaults(Route $route)
    {
        $match = new Match($route, ['name' => 'should override']);
        $this->assertEquals(
            [
                'name'  => 'should override',
                'other' => 'other value'
            ],
            $match->getParameters()
        );
    }
}
