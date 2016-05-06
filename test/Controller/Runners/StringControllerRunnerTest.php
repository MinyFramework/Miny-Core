<?php

namespace Miny\Test\Controller\Runners;

use Miny\Controller\Runners\StringControllerRunner;
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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $log;

    private $containerMap;

    public function setUp()
    {
        $this->controller = $this->getMock(
            '\\Miny\\Controller\\Controller',
            [
                'setRouter',
                'setRouteGenerator',
                'setParameterContainer',
                'indexAction',
                'testAction'
            ],
            [],
            'TestController',
            false
        );
        $this->log = $this->getMock(
            '\\Miny\\Log\\Log',
            [
                'write'
            ],
            [],
            'TestLog',
            false
        );

        $this->container = $this->getMock(
            '\\Miny\\Factory\\Container',
            ['get'],
            [],
            'MockContainer',
            false
        );

        $routerMock = $this->getMockBuilder('\\Miny\\Router\\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerMap = [
            ['TestController', [], false, $this->controller],
            [
                '\\Miny\\Router\\RouteGenerator',
                [],
                false,
                new RouteGenerator($routerMock)
            ],
            ['\\Miny\\Log\\Log', [], false, $this->log],
            ['\\Miny\\Factory\\ParameterContainer', [], false, new ParameterContainer]
        ];

        $this->eventDispatcher = $this->getMock(
            '\\Miny\\Event\\EventDispatcher',
            ['raiseEvent']
        );

        $this->eventDispatcher->expects($this->any())
            ->method('raiseEvent')
            ->will($this->returnArgument(0));

        $this->runner = new StringControllerRunner($this->container, $this->eventDispatcher);
    }

    public function testItCanRunAClosureController()
    {
        $request = new Request('', '');
        $request->get()->set(
            'controller',
            function () {
            }
        );
        $this->assertFalse(
            $this->runner->canRun($request)
        );
    }

    public function testItCanNotRunANonexistentClass()
    {
        $request = new Request('', '');
        $request->get()->set('controller', 'foo');
        $this->assertFalse(
            $this->runner->canRun($request)
        );
    }

    public function testItCanNotRunAClassThatIsNotASubclassOfController()
    {
        $request = new Request('', '');
        $request->get()->set(
            'controller',
            '\\stdClass'
        );
        $this->assertFalse(
            $this->runner->canRun($request)
        );
    }

    public function testItCanRunAControllerByClassname()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('TestController'))
            ->will($this->returnValue($this->controller));

        $request = new Request('', '');
        $request->get()->set(
            'controller',
            'TestController'
        );

        $this->assertTrue($this->runner->canRun($request));
    }

    public function testItCanRunAControllerByShortName()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('TestController'))
            ->will($this->returnValue($this->controller));

        $this->runner->setControllerPattern('%sController');

        $request = new Request('', '');
        $request->get()->set(
            'controller',
            'Test'
        );

        $this->assertTrue($this->runner->canRun($request));
    }

    public function testControllerIsInitialisedOnRun()
    {
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($this->containerMap));

        $this->controller->expects($this->once())
            ->method('setRouteGenerator');

        $this->controller->expects($this->once())
            ->method('setParameterContainer');

        $this->controller->expects($this->once())
            ->method('indexAction');

        $request = new Request('', '');
        $request->get()->set('controller', 'TestController');

        $this->assertTrue($this->runner->canRun($request));

        $this->runner->run($request, new Response);
    }

    public function testActionIsReadFromRequest()
    {
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($this->containerMap));

        $this->controller->expects($this->once())
            ->method('testAction');

        $request = new Request('', '');
        $request->get()->set('controller', 'TestController');
        $request->get()->set('action', 'test');

        $this->assertTrue($this->runner->canRun($request));

        $this->runner->run($request, new Response);
    }
}
