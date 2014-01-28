<?php

namespace Miny\HTTP;

class ResponseHeadersTest extends \PHPUnit_Framework_TestCase
{


    public function testCookies()
    {
        $sender = $this->getMockForAbstractClass('\Miny\HTTP\AbstractHeaderSender');

        $headers = new ResponseHeaders($sender);
        $headers->setCookie('name', 'value');
        $headers->setCookie('foo', 'bar');

        $this->assertContains('value', $headers->getCookies());
        $this->assertContains('bar', $headers->getCookies());

        $headers->removeCookie('foo');

        $this->assertContains('value', $headers->getCookies());
        $this->assertNotContains('bar', $headers->getCookies());
    }

    public function testSend()
    {
        $sender = $this->getMockForAbstractClass('\Miny\HTTP\AbstractHeaderSender');
        $sender->expects($this->at(0))
                ->method('send')
                ->with($this->equalTo('expect: foo, bar'));
        $sender->expects($this->at(1))
                ->method('send')
                ->with($this->equalTo('Foobar'));
        $sender->expects($this->once())
                ->method('sendCookie')
                ->with($this->equalTo('name'), $this->equalTo('value'));

        $headers = new ResponseHeaders($sender);
        $headers->set('Expect', 'foo');
        $headers->set('Expect', 'bar');
        $headers->setCookie('name', 'value');
        $headers->setRaw('Foobar');
        $headers->send();
    }
}
