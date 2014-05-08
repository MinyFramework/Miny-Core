<?php
/**
 * Created by PhpStorm.
 * User: Dániel
 * Date: 2014.05.08.
 * Time: 22:53
 */

namespace Miny\Log;

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
