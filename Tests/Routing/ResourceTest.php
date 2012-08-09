<?php

namespace Miny\Routing;

require_once dirname(__FILE__) . '/../../Routing/RouteCollection.php';
require_once dirname(__FILE__) . '/../../Routing/Resources.php';
require_once dirname(__FILE__) . '/../../Routing/Resource.php';
require_once dirname(__FILE__) . '/../../Routing/Route.php';

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new Resource('foo');
    }

    public function testResourceNameShouldBeItsSingularName()
    {
        $this->assertEquals($this->object->getName(), $this->object->getSingularName());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testShouldNotBeAbleToCallMember()
    {
        $this->object->member('foo', 'bar');
    }

    public function testShouldGenerateMethods()
    {

    }

}

?>
