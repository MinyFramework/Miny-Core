<?php

namespace Miny\Log;

class ProfilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $log;

    public function setUp()
    {
        $this->log = $this->getMockForAbstractClass('\\Miny\\Log\\AbstractLog');

        $this->profiler = new Profiler($this->log, 'test', 'test name');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testStopThrowsExceptionIfNotStarted()
    {
        $this->profiler->stop();
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testStopThrowsExceptionIfNotRunning()
    {
        $this->profiler->start();
        $this->profiler->stop();
        $this->profiler->stop();
    }

    public function testProfilerCallsLogWrite()
    {
        $this->log
            ->expects($this->once())
            ->method('write')
            ->with(
                $this->equalTo(Log::PROFILE),
                $this->equalTo('test'),
                $this->matches('Profiling test name: Run #: 1, Time: %f ms, Memory: %s')
            );

        $this->profiler->start();
        $this->profiler->stop();
    }

    public function testProfilerLogsRunNumber()
    {
        $this->log
            ->expects($this->at(0))
            ->method('write')
            ->with(
                $this->equalTo(Log::PROFILE),
                $this->equalTo('test'),
                $this->matches('Profiling test name: Run #: 1, Time: %f ms, Memory: %s')
            );
        $this->log
            ->expects($this->at(1))
            ->method('write')
            ->with(
                $this->equalTo(Log::PROFILE),
                $this->equalTo('test'),
                $this->matches('Profiling test name: Run #: 2, Time: %f ms, Memory: %s')
            );

        $this->profiler->start();
        $this->profiler->stop();

        $this->profiler->start();
        $this->profiler->stop();
    }

    public function testProfilerLogsMemoryUsage()
    {
        $this->log
            ->expects($this->at(0))
            ->method('write')
            ->with(
                $this->equalTo(Log::PROFILE),
                $this->equalTo('test'),
                $this->logicalAnd(
                    $this->matches(
                        'Profiling test name: Run #: 1, Time: %f ms, Memory: %s'
                    ),
                    $this->logicalNot(
                        $this->matches(
                            'Profiling test name: Run #: 1, Time: %f ms, Memory: 0 B'
                        )
                    )
                )
            );

        $this->profiler->start();
        //this takes a considerable amount of memory
        $str = str_repeat('Hello', 42000);
        $this->profiler->stop();

        unset($str);
    }
}
