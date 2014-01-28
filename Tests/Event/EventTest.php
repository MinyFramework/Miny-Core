<?php

namespace Miny\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{

    public function testHandled()
    {
        $event = new Event('test_event');
        $this->assertFalse($event->isHandled());
        $event->setHandled();
        $this->assertTrue($event->isHandled());
    }

    public function testGetName()
    {
        $event = new Event('test_event');
        $this->assertEquals('test_event', $event->getName());
    }

    public function testGetParameters()
    {
        $parameters = array(
            'p1', 'p2', 'p3'
        );
        $event1     = new Event('name', 'p1', 'p2', 'p3');
        $event2     = new Event('name', $parameters);

        $this->assertEquals($parameters, $event1->getParameters());
        $this->assertEquals($parameters, $event2->getParameters());
    }

    public function testResponse()
    {
        $event    = new Event('test_event');
        //no response set
        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());
        //response set
        $response = 'foo';
        $event->setResponse($response);
        $this->assertTrue($event->hasResponse());
        $this->assertEquals($response, $event->getResponse());
    }
}

?>
