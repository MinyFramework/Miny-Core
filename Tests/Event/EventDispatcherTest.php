<?php

namespace Miny\Event;

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    protected $handler_factory;

    protected function setUp()
    {
        $this->object          = new EventDispatcher;
        $this->handler_factory = function($num) {
            return function()use($num) {
                echo $num;
            };
        };
    }

    public function testRetValOfRaiseEvent()
    {
        $event  = new Event('event');
        $retval = $this->object->raiseEvent($event);
        $this->assertSame($event, $retval);
    }

    public function testStringEvent()
    {
        $retval1 = $this->object->raiseEvent('event');
        $retval2 = $this->object->raiseEvent('event', 'p1', 'p2');

        $this->assertInstanceOf('\Miny\Event\Event', $retval1);
        $this->assertEquals('event', $retval1->getName());
        $this->assertEquals(array('p1', 'p2'), $retval2->getParameters());
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
        $this->object->register('event2', function() {
            return func_get_args();
        });
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
     * @expectedExceptionMessage The first parameter must be an Event object or a string.
     */
    public function testRaiseEventException()
    {
        $this->object->raiseEvent(5);
    }

    /**
     * @expectedException \Miny\Event\Exceptions\EventHandlerException
     * @expectedExceptionMessage Handler is not callable for event event
     */
    public function testRegisterException()
    {
        $this->object->register('event', new \stdClass);
    }
}

?>
