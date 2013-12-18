<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/Route.php';
require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/RouteGenerator.php';

class RouteGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $collection   = new RouteCollection;
        $collection->addRoute(new Route('path/:parameter/:other_parameter'), 'route');
        $collection->addRoute(new Route('?path/:parameter'), 'other_route');
        $this->object = new RouteGenerator($collection);
    }

    public function testGenerate()
    {
        $parameters = array(
            'parameter' => 'with'
        );

        $this->assertEquals('?path/with', $this->object->generate('other_route', $parameters));

        try {
            //missing parameter
            $this->object->generate('route', $parameters);
            $this->fail('Should throw an Exception');
        } catch (\InvalidArgumentException $e) {

        }

        $parameters['other_parameter'] = 'parameter';
        $this->assertEquals('path/with/parameter', $this->object->generate('route', $parameters));
        //extra parameters
        $this->assertEquals('?path/with&other_parameter=parameter', $this->object->generate('other_route', $parameters));
        $parameters['foo']             = 'bar';
        $this->assertEquals('path/with/parameter?foo=bar', $this->object->generate('route', $parameters));
        $parameters['bar']             = 'baz';
        $this->assertEquals('path/with/parameter?foo=bar&bar=baz', $this->object->generate('route', $parameters));
    }
}

?>
