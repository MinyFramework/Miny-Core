<?php

namespace Miny\Test\Event;

use Miny\Event\Event;

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
        $parameters = [
            'p1',
            'p2',
            'p3'
        ];
        $event      = new Event('name', 'p1', 'p2', 'p3');

        $this->assertEquals($parameters, $event->getParameters());
    }

    public function testResponse()
    {
        $event = new Event('test_event');
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
