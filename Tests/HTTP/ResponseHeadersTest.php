<?php

namespace Miny\HTTP;

class ResponseHeadersTest extends \PHPUnit_Framework_TestCase
{

    public function testSend()
    {
        $sender = $this->getMockForAbstractClass('\Miny\HTTP\AbstractHeaderSender');
        $sender->expects($this->at(0))
                ->method('send')
                ->with($this->equalTo('expect: foo, bar'));
        $sender->expects($this->at(1))
                ->method('send')
                ->with($this->equalTo('Foobar'));

        $headers = new ResponseHeaders($sender);
        $headers->set('Expect', 'foo');
        $headers->set('Expect', 'bar');
        $headers->setRaw('Foobar');
        $headers->send();
    }
}
