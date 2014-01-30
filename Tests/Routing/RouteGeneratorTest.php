<?php

namespace Miny\Routing;

class RouteGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $collection;

    protected function setUp()
    {
        $collection = new RouteCollection;
        $collection->addRoute(new Route('path/:parameter/:other_parameter'), 'route');
        $collection->addRoute(new Route('?path/:parameter'), 'other_route');
        $this->collection = $collection;
    }

    public function testGenerate()
    {
        $parameters = array(
            'parameter' => 'with'
        );
        $generator  = new RouteGenerator($this->collection);

        $this->assertEquals('?path/with', $generator->generate('other_route', $parameters));

        $parameters['other_parameter'] = 'parameter';
        $this->assertEquals('path/with/parameter', $generator->generate('route', $parameters));
        //extra parameters
        $this->assertEquals('?path/with&other_parameter=parameter', $generator->generate('other_route', $parameters));

        $parameters['foo'] = 'bar';
        $parameters['bar'] = 'baz';

        $this->assertEquals('path/with/parameter?foo=bar&bar=baz', $generator->generate('route', $parameters));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Parameters not set: parameter, other_parameter
     */
    public function testGenerateMissingParameters()
    {
        $generator = new RouteGenerator($this->collection);
        $generator->generate('route');
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Route not found: foo
     */
    public function testGenerateMissingRoute()
    {
        $generator = new RouteGenerator($this->collection);
        $generator->generate('foo');
    }

    public function testGenerateWithMissingParametersSuppliedByRoute()
    {
        $this->collection->addRoute(new Route('path/:a', 'GET', array('a' => 'foo')), 'some_route');
        $generator = new RouteGenerator($this->collection);
        $this->assertEquals('path/foo', $generator->generate('some_route'));
    }

    public function testNoShortUrls()
    {
        $parameters = array(
            'parameter'       => 'with',
            'other_parameter' => 'parameter',
            'extra'           => 'foo'
        );

        $generator = new RouteGenerator($this->collection, false);
        $this->assertEquals('?path=path%2Fwith%2Fparameter&extra=foo', $generator->generate('route', $parameters));
    }
}

?>
