<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/Resources.php';
require_once dirname(__FILE__) . '/../../Routing/Route.php';

class ResourcesTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        //$this->object = new Resources();
    }

    public function singularizeProvider()
    {
        return array(
            array('apple', 'apples'),
            array('apple', 'apple'),
        );
    }

    /**
     * @dataProvider singularizeProvider
     */
    public function testSingularize($expected, $name)
    {
        $this->assertEquals($expected, Resources::singularize($name));
    }

}

?>
