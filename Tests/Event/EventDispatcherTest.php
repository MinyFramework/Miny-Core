<?php

namespace Miny\Event;

require_once dirname(__FILE__) . '/../../Event/Event.php';
require_once dirname(__FILE__) . '/../../Event/EventDispatcher.php';

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    protected $handler_factory;

    protected function setUp()
    {
        $this->object = new EventDispatcher;
        $this->handler_factory = function($num) {
                    return function()use($num) {
                                echo $num;
                            };
                };
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
        $this->object->register('event2', function(Event $event){
            $event->setResponse(func_get_args());
        });
        $this->object->raiseEvent($event);
        $this->assertEquals(array($event, 'p1', 'p2'), $event->getResponse());
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

}

?>
