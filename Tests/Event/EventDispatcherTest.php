<?php

namespace Miny\Event;

use InvalidArgumentException;

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcher
     */
    protected $object;
    protected $handler_factory;

    protected function setUp()
    {
        $this->object          = new EventDispatcher;
        $this->handler_factory = function ($num) {
            return function () use ($num) {
                echo $num;
            };
        };
    }

    public function testRetValOfRaiseEvent()
    {
        $event = new Event('event');
        $this->assertSame($event, $this->object->raiseEvent($event));
    }

    public function testStringEvent()
    {
        $event1 = $this->object->raiseEvent('event');
        $event2 = $this->object->raiseEvent('event', 'p1', 'p2');

        $this->assertInstanceOf('\Miny\Event\Event', $event1);
        $this->assertEquals('event', $event1->getName());
        $this->assertEquals(array('p1', 'p2'), $event2->getParameters());
    }

    public function testHandleEventWithHandler()
    {
        $handler_factory = $this->handler_factory;
        $this->expectOutputString('01');
        $this->object->register('event', $handler_factory(0));
        $this->object->register('event', $handler_factory(1));

        $event = new Event('event');
        $this->object->raiseEvent($event);
        $this->assertTrue($event->isHandled());
    }

    public function testShouldNotHandleEventWithoutHandler()
    {
        $event = new Event('event');
        $this->object->raiseEvent($event);
        $this->assertFalse($event->isHandled());
    }

    public function testShouldPassParametersToHandler()
    {
        $event = new Event('event2', 'p1', 'p2');
        $this->object->register(
            'event2',
            function () {
                return func_get_args();
            }
        );
        $this->object->raiseEvent($event);
        $this->assertEquals(array('p1', 'p2'), $event->getResponse());
    }

    public function testInsertHandlerIntoSequence()
    {
        $handler_factory = $this->handler_factory;
        $this->expectOutputString('012');

        $this->object->register('event', $handler_factory(0));
        $this->object->register('event', $handler_factory(2));
        $this->object->register('event', $handler_factory(1), 1);

        $this->object->raiseEvent(new Event('event'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRaiseEventException()
    {
        $this->object->raiseEvent(5);
    }

    /**
     * @expectedException \Miny\Event\Exceptions\EventHandlerException
     */
    public function testRegisterException()
    {
        $this->object->register('event', new \stdClass);
    }
}
