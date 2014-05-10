<?php

namespace Miny\Controller\Runners;

use Miny\Factory\ParameterContainer;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Miny\Router\RouteGenerator;

class StringControllerRunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $controller;

    /**
     * @var StringControllerRunner
     */
    private $runner;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    private $containerMap;

    public function setUp()
    {
        $this->controller = $this->getMock(
            '\\Miny\\Controller\\Controller',
            array(
                'setRouter',
                'setRouteGenerator',
                'setParameterContainer',
                'indexAction',
                'testAction'
            ),
            array(),
            'TestController',
            false
        );

        $this->container = $this->getMock(
            '\\Miny\\Factory\\Container',
            array('get'),
            array(),
            'MockContainer',
            false
        );

        $routerMock = $this->getMockBuilder('\\Miny\\Router\\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerMap = array(
            array('TestController', array(), false, $this->controller),
            array('\\Miny\\Router\\Router', array(), false, $routerMock),
            array(
                '\\Miny\\Router\\RouteGenerator',
                array(),
                false,
                new RouteGenerator($routerMock)
            ),
            array('\\Miny\\Factory\\ParameterContainer', array(), false, new ParameterContainer)
        );

        $this->eventDispatcher = $this->getMock(
            '\\Miny\\Event\\EventDispatcher',
            array('raiseEvent')
        );

        $this->eventDispatcher->expects($this->any())
            ->method('raiseEvent')
            ->will($this->returnArgument(0));

        $this->runner = new StringControllerRunner($this->container, $this->eventDispatcher);
    }

    public function testItCanNotRunANonStringController()
    {
        $this->assertFalse(
            $this->runner->canRun(
                function () {
                }
            )
        );
    }

    public function testItCanNotRunANonexistentClass()
    {
        $this->assertFalse(
            $this->runner->canRun('foo')
        );
    }

    public function testItCanNotRunAClassThatIsNotASubclassOfController()
    {
        $this->assertFalse(
            $this->runner->canRun('\\stdClass')
        );
    }

    public function testItCanRunAControllerByClassname()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('TestController'))
            ->will($this->returnValue($this->controller));

        $this->assertTrue($this->runner->canRun('TestController'));
    }

    public function testItCanRunAControllerByShortName()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('TestController'))
            ->will($this->returnValue($this->controller));

        $this->runner->setControllerPattern('%sController');
        $this->assertTrue($this->runner->canRun('Test'));
    }

    public function testControllerIsInitialisedOnRun()
    {
        $this->container->expects($this->exactly(4))
            ->method('get')
            ->will($this->returnValueMap($this->containerMap));

        $this->controller->expects($this->once())
            ->method('setRouter');

        $this->controller->expects($this->once())
            ->method('setRouteGenerator');

        $this->controller->expects($this->once())
            ->method('setParameterContainer');

        $this->controller->expects($this->once())
            ->method('indexAction');

        $this->assertTrue($this->runner->canRun('TestController'));

        $this->runner->run(new Request('', ''), new Response);
    }

    public function testActionIsReadFromRequest()
    {
        $this->container->expects($this->exactly(4))
            ->method('get')
            ->will($this->returnValueMap($this->containerMap));

        $this->controller->expects($this->once())
            ->method('testAction');

        $this->assertTrue($this->runner->canRun('TestController'));

        $request = new Request('', '');
        $request->get()->set('action', 'test');
        $this->runner->run($request, new Response);
    }
}
