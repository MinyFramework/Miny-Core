<?php

namespace Miny\Test\Controller;

use Miny\Controller\Events\ControllerFinishedEvent;
use Miny\Controller\Events\ControllerLoadedEvent;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class AbstractControllerRunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractControllerRunner
     */
    private $runner;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMock('\\Miny\\Event\\EventDispatcher');

        $this->eventDispatcher->expects($this->any())
            ->method('raiseEvent')
            ->will($this->returnArgument(0));

        $this->runner = $this->getMockForAbstractClass(
            '\\Miny\\Controller\\AbstractControllerRunner',
            [$this->eventDispatcher],
            'ControllerRunnerStub',
            true,
            true,
            true,
            ['initController']
        );
        $this->runner->expects($this->any())
            ->method('createLoadedEvent')
            ->will($this->returnValue(new ControllerLoadedEvent('controller', 'action')));

        $this->runner->expects($this->any())
            ->method('createFinishedEvent')
            ->will(
                $this->returnCallback(
                    function ($arg) {
                        return new ControllerFinishedEvent('controller', 'action', $arg);
                    }
                )
            );
    }

    public function testInitControllerIsCalled()
    {
        $request  = new Request('GET', '');
        $response = new Response;

        $this->runner->expects($this->once())
            ->method('initController')
            ->with(
                $this->callback(
                    function ($requestArg) use ($request) {
                        return $requestArg === $request;
                    }
                ),
                $this->callback(
                    function ($responseArg) use ($response) {
                        return $responseArg === $response;
                    }
                )
            );

        $this->runner->run($request, $response);
    }

    public function testEventsAreFired()
    {
        $this->eventDispatcher->expects($this->at(0))
            ->method('raiseEvent')
            ->with(
                $this->callback(
                    function ($argument) {
                        return $argument instanceof ControllerLoadedEvent;
                    }
                )
            );

        $this->eventDispatcher->expects($this->at(1))
            ->method('raiseEvent')
            ->with(
                $this->callback(
                    function ($argument) {
                        return $argument instanceof ControllerFinishedEvent;
                    }
                )
            );
        $this->runner->run(new Request('GET', ''), new Response);
    }

    public function testFinishedEventContainsReturnValue()
    {
        $this->eventDispatcher->expects($this->at(1))
            ->method('raiseEvent')
            ->with(
                $this->callback(
                    function (ControllerFinishedEvent $argument) {
                        return $argument->getReturnValue() === 'retVal';
                    }
                )
            );

        $this->runner->expects($this->once())
            ->method('runController')
            ->will($this->returnValue('retVal'));

        $this->runner->run(new Request('GET', ''), new Response);
    }

    public function testControllerIsNotExecutedWhenLoadedEventIsNotHandledOrDoesNotHaveResponse()
    {
        $this->runner->expects($this->any())
            ->method('createLoadedEvent')
            ->will(
                $this->returnCallback(
                    function () {
                        return new ControllerLoadedEvent('controller', 'action');
                    }
                )
            );
        $this->eventDispatcher->expects($this->at(0))
            ->method('raiseEvent')
            ->will(
                $this->returnCallback(
                    function (ControllerLoadedEvent $event) {
                        $event->setResponse(new Response);

                        return $event;
                    }
                )
            );
        $this->eventDispatcher->expects($this->at(2))
            ->method('raiseEvent')
            ->will(
                $this->returnCallback(
                    function (ControllerLoadedEvent $event) {
                        $event->setHandled();
                        $event->setResponse(null);

                        return $event;
                    }
                )
            );

        $this->eventDispatcher->expects($this->at(4))
            ->method('raiseEvent')
            ->will(
                $this->returnCallback(
                    function (ControllerLoadedEvent $event) {
                        $event->setHandled();
                        $event->setResponse(new Response);

                        return $event;
                    }
                )
            );

        $this->runner->expects($this->exactly(2))
            ->method('runController');

        $this->runner->run(new Request('GET', ''), new Response);
        $this->runner->run(new Request('GET', ''), new Response);
        $this->runner->run(new Request('GET', ''), new Response);
    }
}
