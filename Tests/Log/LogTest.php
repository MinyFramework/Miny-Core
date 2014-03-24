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

    public function testThatWriterIsRemoved()
    {

        $this->writerMock
            ->expects($this->once())
            ->method('add');

        $this->writerMock
            ->expects($this->never())
            ->method('commit');

        $this->log->registerWriter($this->writerMock, Log::INFO);

        $this->log->write(Log::INFO, '', '');

        $this->log->removeWriter($this->writerMock);

        $this->log->write(Log::INFO, '', '');
    }
}
