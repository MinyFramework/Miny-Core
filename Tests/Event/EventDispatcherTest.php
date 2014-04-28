<?php

namespace Miny\Event;

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

    public function testRaiseEventReturnsTheRaisedEvent()
    {
        $event = new Event('event');
        $this->assertSame($event, $this->object->raiseEvent($event));
    }

    public function testEventThatHaveHandlerRegisteredIsHandled()
    {
        $handler_factory = $this->handler_factory;
        $this->expectOutputString('01');
        $this->object->register('event', $handler_factory(0));
        $this->object->register('event', $handler_factory(1));

        $event = $this->object->raiseEvent(new Event('event'));
        $this->assertTrue($event->isHandled());
    }

    public function testBatchEventRegistration()
    {
        $handler_factory = $this->handler_factory;

        $this->expectOutputString('012');
        $this->object->registerHandlers('event', array($handler_factory(0), $handler_factory(1)));
        $this->object->register('event2', $handler_factory(2));

        $this->object->raiseEvent(new Event('event'));
        $this->object->raiseEvent(new Event('event2'));
    }

    public function testEventWithoutHandlerIsNotHandled()
    {
        $event = new Event('event');
        $this->object->raiseEvent($event);
        $this->assertFalse($event->isHandled());
    }

    public function testParametersArePassedToHandler()
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

    public function testHandlerCanBeInsertedIntoASequenceOfHandlers()
    {
        $handler_factory = $this->handler_factory;
        $this->expectOutputString('012');

        $this->object->register('event', $handler_factory(0));
        $this->object->register('event', $handler_factory(2));
        $this->object->register('event', $handler_factory(1), 1);

        $this->object->raiseEvent(new Event('event'));
    }

    /**
     * @expectedException \Miny\Event\Exceptions\EventHandlerException
     */
    public function testRegisterShouldThrowExceptionWhenHandlerIsNotCallable()
    {
        $this->object->register('event', new \stdClass);
    }
}
