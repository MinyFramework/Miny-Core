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
            array('raiseEvent')
        );

        $this->eventDispatcher->expects($this->any())
            ->method('raiseEvent')
            ->will($this->returnArgument(0));

        $this->runner = new ClosureControllerRunner($this->eventDispatcher);
    }

    public function testItCanRunAClosureController()
    {
        $this->assertTrue(
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

    public function testActionRequestAndResponseArePassedToClosure()
    {
        $response = new Response;
        $request  = new Request('', '');
        $request->get()->set('action', 'test');

        $test = $this;

        $this->assertTrue(
            $this->runner->canRun(
                function ($actionArg, $requestArg, $responseArg) use ($request, $response, $test) {
                    $test->assertEquals('test', $actionArg);
                    $test->assertSame($request, $requestArg);
                    $test->assertSame($response, $responseArg);
                }
            )
        );
        $this->runner->run($request, $response);
    }
}
