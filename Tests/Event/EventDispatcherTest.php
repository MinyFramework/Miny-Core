<?php

namespace Miny\Event;

require_once dirname(__FILE__) . '/../../Event/Event.php';
require_once dirname(__FILE__) . '/../../Event/EventHandler.php';
require_once dirname(__FILE__) . '/../../Event/EventDispatcher.php';

class TestHandler extends EventHandler
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function handle(Event $event)
    {
        echo $this->id;
    }

    public function foo_handler(Event $event)
    {
        echo 'foo_handler';
    }

    public function handler_with_response(Event $event)
    {
        $event->setResponse('response_string');
    }

}

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new EventDispatcher;
    }

    public function testHandleEventWithHandler()
    {
        $this->object->setHandler('event', new TestHandler(0));
        $this->object->setHandler('event', new TestHandler(1));

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

    public function testInsertHandlerIntoSequence()
    {
        $this->expectOutputString('012');

        $this->object->setHandler('event', new TestHandler(0));
        $this->object->setHandler('event', new TestHandler(2));
        $this->object->setHandler('event', new TestHandler(1), NULL, 1);

        $this->object->raiseEvent(new Event('event'));
    }

    public function testNamedHandlerMethod()
    {
        $handler_method_name = 'foo_handler';
        $this->expectOutputString($handler_method_name);

        $this->object->setHandler('event', new TestHandler(0), $handler_method_name);
        $this->object->raiseEvent(new Event('event'));
    }

}

?>
