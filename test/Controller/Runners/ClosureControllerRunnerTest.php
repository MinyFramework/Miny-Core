<?php

namespace Miny\Controller\Runners;

use Miny\HTTP\Request;
use Miny\HTTP\Response;

class ClosureControllerRunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClosureControllerRunner
     */
    private $runner;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMock(
            '\\Miny\\Event\\EventDispatcher',
            ['raiseEvent']
        );

        $this->eventDispatcher->expects($this->any())
            ->method('raiseEvent')
            ->will($this->returnArgument(0));

        $this->runner = new ClosureControllerRunner($this->eventDispatcher);
    }

    public function testItCanRunAClosureController()
    {
        $request = new Request('', '');
        $request->get()->set(
            'controller',
            function () {
            }
        );
        $this->assertTrue(
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

    public function testActionRequestAndResponseArePassedToClosure()
    {
        $response = new Response;
        $request  = new Request('', '');
        $request->get()->set('action', 'test');

        $test = $this;

        $request->get()->set(
            'controller',
            function ($actionArg, $requestArg, $responseArg) use ($request, $response, $test) {
                $test->assertEquals('test', $actionArg);
                $test->assertSame($request, $requestArg);
                $test->assertSame($response, $responseArg);
            }
        );
        $this->assertTrue(
            $this->runner->canRun($request)
        );
        $this->runner->run($request, $response);
    }
}
