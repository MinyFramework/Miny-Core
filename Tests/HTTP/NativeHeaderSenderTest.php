<?php

namespace Miny\HTTP;

/**
 * @runTestsInSeparateProcesses
 */
class NativeHeaderSenderTest extends \PHPUnit_Framework_TestCase
{

    public function testSend()
    {
        $sender = new NativeHeaderSender;
        ob_start();
        $sender->send('Some header');

        $sent = xdebug_get_headers();
        $this->assertNotEmpty($sent);
        $this->assertContains('Some header', $sent);

        header_remove();
    }
}
