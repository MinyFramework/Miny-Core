<?php

namespace Miny\HTTP;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    protected $response;
    protected $mock;

    public function setUp()
    {
        $this->mock = $this->getMock('\Miny\HTTP\ResponseHeaders',
                array('setCookie', 'getCookies', 'removeCookie', 'send', 'set', 'setRaw', 'has'), array(), 'mockHeaders');

        $this->response = new Response($this->mock);
    }

    public function testSetGetContent()
    {
        $this->response->addContent('content');
        $this->assertEquals('content', $this->response->getContent());
    }

    public function testResponseShouldNotAcceptInvalidStatusCodes()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->response->setCode(603);
    }

    public function statusCodeProvider()
    {
        return array(
            array(100, 'Continue'),
            array(101, 'Switching Protocols'),
            array(200, 'OK'),
            array(201, 'Created'),
            array(202, 'Accepted'),
            array(203, 'Non-Authoritative Information'),
            array(204, 'No Content'),
            array(205, 'Reset Content'),
            array(206, 'Partial Content'),
            array(300, 'Multiple Choices'),
            array(301, 'Moved Permanently'),
            array(302, 'Found'),
            array(303, 'See Other'),
            array(304, 'Not Modified'),
            array(305, 'Use Proxy'),
            array(306, 'Temporary Redirect'),
            array(400, 'Bad Request'),
            array(401, 'Unauthorized'),
            array(402, 'Payment Required'),
            array(403, 'Forbidden'),
            array(404, 'Not Found'),
            array(405, 'Method Not Allowed'),
            array(406, 'Not Acceptable'),
            array(407, 'Proxy Authentication Required'),
            array(408, 'Request Timeout'),
            array(409, 'Conflict'),
            array(410, 'Gone'),
            array(411, 'Length Required'),
            array(412, 'Precondition Failed'),
            array(413, 'Request Entity Too Large'),
            array(414, 'Request-URI Too Long'),
            array(415, 'Unsupported Media Type'),
            array(416, 'Requested Range Not Satisfiable'),
            array(417, 'Expectation Failed'),
            array(500, 'Internal Server Error'),
            array(501, 'Not Implemented'),
            array(502, 'Bad Gateway'),
            array(503, 'Service Unavailable'),
            array(504, 'Gateway Timeout'),
            array(505, 'HTTP Version Not Supported')
        );
    }

    /**
     * @dataProvider statusCodeProvider
     */
    public function testResponseStatusCodes($code, $message)
    {
        $this->response->setCode($code);
        $this->assertTrue($this->response->isCode($code));
        $this->assertEquals($message, $this->response->getStatus());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid status code: 523
     */
    public function testInvalidStatusCode()
    {
        $this->response->setCode(523);
    }

    public function testGetHeaders()
    {
        $this->assertInstanceOf('\Miny\HTTP\ResponseHeaders', $this->response->getHeaders());
    }

    public function testSetHeaders()
    {
        $this->mock->expects($this->at(0))
                ->method('set')
                ->with($this->equalTo('Location'), $this->equalTo('url'));

        $this->mock->expects($this->at(1))
                ->method('set')
                ->with($this->equalTo('Location'), $this->equalTo('otherurl'));

        $this->response->redirect('url');
        $this->assertEquals(301, $this->response->getCode());

        $this->response->redirect('otherurl', 302);
        $this->assertEquals(302, $this->response->getCode());
    }

    public function testCookies()
    {
        $this->assertEmpty($this->response->getCookies());

        $this->mock->expects($this->once())
                ->method('setCookie')
                ->with($this->equalTo('foo'), $this->equalTo('bar'));

        $this->mock->expects($this->once())
                ->method('getCookies');
        $this->mock->expects($this->once())
                ->method('removeCookie');

        $this->response->setCookie('foo', 'bar');
        $this->response->getCookies();
        $this->response->removeCookie('foo');
    }

    public function testContent()
    {
        $this->assertEmpty((string) $this->response);

        $this->response->addContent('string');

        $this->assertEquals('string', (string) $this->response);

        $this->response->clearContent();

        $this->assertEmpty((string) $this->response);
    }

    public function testAddResponse()
    {
        $this->response->addContent('string');

        $response = new Response;
        $response->addContent(' and another');
        $this->response->addResponse($response);

        $this->assertEquals('string and another', (string) $this->response);
    }

    public function testSerialize()
    {
        $this->response->addContent('content');
        $this->response->setCode(301);

        $response = unserialize(serialize($this->response));

        $this->assertEquals('content', (string) $response);
        $this->assertTrue($response->isCode(301));
        $this->assertInstanceof('\Miny\HTTP\ResponseHeaders', $response->getHeaders());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSend()
    {
        $this->mock->expects($this->exactly(2))
                ->method('has')
                ->with($this->equalTo('location'))
                ->will($this->onConsecutiveCalls(true, false));

        $this->response->addContent('content');

        ob_start();
        $this->response->send();
        $this->assertEmpty(ob_get_clean());

        $this->mock->expects($this->once())
                ->method('setRaw')
                ->with($this->equalTo('HTTP/1.1 200: OK'));

        $this->mock->expects($this->once())
                ->method('setCookie')
                ->with($this->equalTo('foo'), $this->equalTo('bar'));

        $this->mock->expects($this->once())
                ->method('send');

        $this->response->setCookie('foo', 'bar');

        ob_start();
        $this->response->send();
        $this->assertEquals('content', ob_get_clean());
    }
}

?>
