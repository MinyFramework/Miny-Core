<?php

namespace Miny\Event;

require_once dirname(__FILE__) . '/../../Event/Event.php';

class EventTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new Event('test_event', 'p1', 'p2', 'p3');
    }

    public function testHandled()
    {
        $this->assertFalse($this->object->isHandled());
        $this->object->setHandled();
        $this->assertTrue($this->object->isHandled());
    }

    public function testGetName()
    {
        $this->assertEquals('test_event', $this->object->getName());
    }

    public function test__toString()
    {
        $this->assertEquals('test_event', (string) $this->object);
    }

    public function testGetParameters()
    {
        $parameters = array(
            'p1', 'p2', 'p3'
        );
        $this->assertEquals($parameters, $this->object->getParameters());
    }

    public function testResponse()
    {
        //no response set
        $this->assertFalse($this->object->hasResponse());
        $this->assertNull($this->object->getResponse());
        //response set
        $response = 'foo';
        $this->object->setResponse($response);
        $this->assertTrue($this->object->hasResponse());
        $this->assertEquals($response, $this->object->getResponse());
    }

}

?>
