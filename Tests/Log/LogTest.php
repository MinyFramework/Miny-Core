<?php

namespace Miny\Log;

class LogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $writerMock;
    /**
     * @var Log
     */
    private $log;

    public function setUp()
    {
        $this->writerMock = $this->getMockForAbstractClass('\\Miny\\Log\\AbstractLogWriter');
        $this->log        = new Log();
    }

    public function testThatFlushIsCalledWhenLimitIsReached()
    {
        $this->writerMock
            ->expects($this->exactly(2)) // because this writer is assigned to all 5 log levels
            ->method('commit');

        $this->log->registerWriter($this->writerMock);
        $this->log->setFlushLimit(5);

        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::ERROR, '', '');
        $this->log->write(Log::ERROR, '', '');
        $this->log->write(Log::WARNING, '', '');
        $this->log->write(Log::WARNING, '', '');
        $this->log->write(Log::DEBUG, '', '');
        $this->log->write(Log::DEBUG, '', '');
        $this->log->write(Log::PROFILE, '', '');
        $this->log->write(Log::PROFILE, '', '');
    }

    public function testThatOnlyTheGivenLevelsArePropagatedToWriter()
    {
        $this->writerMock
            ->expects($this->exactly(2))
            ->method('add')
            ->with($this->isInstanceOf('\\Miny\\Log\\LogMessage'));

        $this->log->registerWriter($this->writerMock, Log::INFO);
        $this->log->setFlushLimit(1);

        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::ERROR, '', '');
        $this->log->write(Log::DEBUG, '', '');
        $this->log->write(Log::PROFILE, '', '');
        $this->log->write(Log::WARNING, '', '');
    }

    public function testThatPendingMessagesAreGivenToWriter()
    {
        $this->writerMock
            ->expects($this->exactly(2))
            ->method('add')
            ->with($this->isInstanceOf('\\Miny\\Log\\LogMessage'));

        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');

        $this->log->registerWriter($this->writerMock, Log::INFO);
        $this->log->flush();
    }

    public function testThatWriterIsRemoved()
    {
        $this->writerMock
            ->expects($this->never())
            ->method('add');

        $this->writerMock
            ->expects($this->never())
            ->method('commit');

        $this->log->registerWriter($this->writerMock, Log::INFO);

        $this->log->write(Log::INFO, '', '');

        $this->log->removeWriter($this->writerMock);

        $this->log->write(Log::INFO, '', '');
    }

    public function testThatProfilerIsReturned()
    {
        $this->assertInstanceOf(
            '\\Miny\\Log\\Profiler',
            $this->log->startProfiling('test', 'test')
        );
    }

    public function testThatMultipleStartWithSameNameReturnsSameProfiler()
    {
        $profiler = $this->log->startProfiling('test', 'test');

        $this->assertSame($profiler, $this->log->startProfiling('test', 'test'));
        $this->assertSame($profiler, $this->log->startProfiling('test', 'test'));
        $this->assertSame($profiler, $this->log->startProfiling('test', 'test'));
    }

    public function testThatFlushIsNotCalledWhenLimitIsSetAndBufferIsNotFull()
    {
        $this->writerMock
            ->expects($this->never())
            ->method('commit');

        $this->log->registerWriter($this->writerMock, Log::INFO);

        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');

        $this->log->setFlushLimit(10);
    }

    public function testThatFlushIsCalledWhenLimitIsSetAndBufferIsFull()
    {
        $this->writerMock
            ->expects($this->once())
            ->method('commit');

        $this->log->registerWriter($this->writerMock, Log::INFO);

        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');
        $this->log->write(Log::INFO, '', '');

        $this->log->setFlushLimit(5);
    }

    public function testThatGetLevelNameReturnsUnknown()
    {
        $this->assertStringStartsNotWith('Unknown', Log::getLevelName(Log::PROFILE));
        $this->assertStringStartsNotWith('Unknown', Log::getLevelName(Log::INFO));
        $this->assertStringStartsNotWith('Unknown', Log::getLevelName(Log::DEBUG));
        $this->assertStringStartsNotWith('Unknown', Log::getLevelName(Log::WARNING));
        $this->assertStringStartsNotWith('Unknown', Log::getLevelName(Log::ERROR));
        $this->assertStringStartsWith('Unknown', Log::getLevelName(150));
    }

    public function testThatMessagesAreFormatted()
    {
        $this->writerMock->expects($this->at(0))
            ->method('add')
            ->with(
                $this->callback(
                    function (LogMessage $message) {
                        return $message->getMessage() == 'message formatted with arguments';
                    }

                )
            );
        $this->writerMock->expects($this->at(1))
            ->method('add')
            ->with(
                $this->callback(
                    function (LogMessage $message) {
                        $string = 'message formatted with arguments from array';

                        return $message->getMessage() == $string;
                    }
                )
            );
        $this->log->registerWriter($this->writerMock, Log::INFO);

        $this->log->write(Log::INFO, 'category', 'message %s %s', 'formatted', 'with arguments');
        $this->log->write(
            Log::INFO,
            'category',
            'message %s %s',
            array('formatted', 'with arguments from array')
        );
        $this->log->flush();
    }
}
