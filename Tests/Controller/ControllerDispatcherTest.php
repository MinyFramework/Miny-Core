<?php

namespace Miny\Controller;

use Miny\HTTP\Request;
use Miny\HTTP\Response;

class ControllerDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var ControllerDispatcher
     */
    private $dispatcher;

    public function setUp()
    {
        $this->container = $this->getMock(
            '\\Miny\\Factory\\Container',
            array('get', 'setInstance'),
            array(),
            'MockContainer',
            false
        );

        $this->dispatcher = new ControllerDispatcher($this->container);
    }

    /**
     * @expectedException \Miny\Controller\Exceptions\InvalidControllerException
     */
    public function testDispatcherThrowsExceptionWhenNoRunnerHasMatched()
    {
        $request = new Request('', '');
        $request->get()->set('controller', '');

        $this->container->expects($this->once())
            ->method('get')
            ->will($this->returnValue(new Response()));

        $this->dispatcher->runController($request);
    }

    public function testDispatcherStopsAtTheFirstMatchingRunner()
    {
        $dummyRunner = $this->getMockBuilder('\\Miny\\Controller\\AbstractControllerRunner')
            ->disableOriginalConstructor()
            ->setMethods(array('run', 'canRun'))
            ->getMockForAbstractClass();

        $mockRunner = $this->getMockBuilder('\\Miny\\Controller\\AbstractControllerRunner')
            ->disableOriginalConstructor()
            ->setMethods(array('run', 'canRun'))
            ->getMockForAbstractClass();

        $dummyRunner->expects($this->exactly(3))
            ->method('canRun')
            ->with($this->equalTo('controllerParam'))
            ->will($this->returnValue(false));

        $mockRunner->expects($this->once())
            ->method('canRun')
            ->with($this->equalTo('controllerParam'))
            ->will($this->returnValue(true));

        $dummyRunner->expects($this->never())
            ->method('run');

        $mockRunner->expects($this->once())
            ->method('run')
            ->will($this->returnArgument(1));

        $this->dispatcher->addRunner($dummyRunner);
        $this->dispatcher->addRunner($dummyRunner);
        $this->dispatcher->addRunner($dummyRunner);
        $this->dispatcher->addRunner($mockRunner);
        $this->dispatcher->addRunner($dummyRunner);

        $request = new Request('', '');
        $request->get()->set('controller', 'controllerParam');

        $this->container->expects($this->once())
            ->method('get')
            ->will($this->returnValue(new Response()));

        $this->dispatcher->runController($request);
    }

    public function testTheOldRequestIsRestored()
    {
        $mockRunner = $this->getMockBuilder('\\Miny\\Controller\\AbstractControllerRunner')
            ->disableOriginalConstructor()
            ->setMethods(array('run', 'canRun'))
            ->getMockForAbstractClass();

        $mockRunner->expects($this->once())
            ->method('canRun')
            ->with($this->equalTo('controllerParam'))
            ->will($this->returnValue(true));

        $mockRunner->expects($this->once())
            ->method('run')
            ->will($this->returnArgument(1));

        $this->dispatcher->addRunner($mockRunner);

        $request = new Request('', '');
        $request->get()->set('controller', 'controllerParam');

        $oldResponse = new Response;
        $response    = new Response;

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('\\Miny\\HTTP\\Response'),
                $this->equalTo(array()),
                $this->equalTo(true)
            )
            ->will($this->returnValue($response));

        $this->container->expects($this->exactly(2))
            ->method('setInstance')
            ->will(
                $this->returnValueMap(
                    array(
                        array($response, null, $oldResponse),
                        array($oldResponse, null, $response)
                    )
                )
            );

        $dispatcherResponse = $this->dispatcher->runController($request);
        $this->assertSame($response, $dispatcherResponse);
    }
}
