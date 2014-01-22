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
        $sender->sendCookie('foo', 'bar');

        $sent = xdebug_get_headers();
        $this->assertNotEmpty($sent);
        $this->assertContains('Some header', $sent);
        $this->assertContains('Set-Cookie: foo=bar', $sent);

        header_remove();
    }
}
