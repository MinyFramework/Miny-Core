<?php

namespace Miny\Test\Log;

use Miny\Log\LogMessage;

class LogMessageTest extends \PHPUnit_Framework_TestCase
{
    public function testLogMessage()
    {
        $lm = new LogMessage('level', 'time', 'category', 'message');

        $this->assertEquals('level', $lm->getLevel());
        $this->assertEquals('time', $lm->getTime());
        $this->assertEquals('category', $lm->getCategory());
        $this->assertEquals('message', $lm->getMessage());
    }
}
