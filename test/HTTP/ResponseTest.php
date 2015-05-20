<?php

namespace Miny\HTTP;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mock;

    public function setUp()
    {
        $this->mock = $this->getMock(
            '\Miny\HTTP\ResponseHeaders',
            [
                'setCookie',
                'getCookies',
                'removeCookie',
                'send',
                'set',
                'setRaw',
                'has'
            ],
            [
                $this->getMockForAbstractClass('Miny\\HTTP\\AbstractHeaderSender')
            ],
            'mockHeaders'
        );

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
        return [
            [100, 'Continue'],
            [101, 'Switching Protocols'],
            [200, 'OK'],
            [201, 'Created'],
            [202, 'Accepted'],
            [203, 'Non-Authoritative Information'],
            [204, 'No Content'],
            [205, 'Reset Content'],
            [206, 'Partial Content'],
            [300, 'Multiple Choices'],
            [301, 'Moved Permanently'],
            [302, 'Found'],
            [303, 'See Other'],
            [304, 'Not Modified'],
            [305, 'Use Proxy'],
            [306, 'Switch Proxy'],
            [307, 'Temporary Redirect'],
            [400, 'Bad Request'],
            [401, 'Unauthorized'],
            [402, 'Payment Required'],
            [403, 'Forbidden'],
            [404, 'Not Found'],
            [405, 'Method Not Allowed'],
            [406, 'Not Acceptable'],
            [407, 'Proxy Authentication Required'],
            [408, 'Request Timeout'],
            [409, 'Conflict'],
            [410, 'Gone'],
            [411, 'Length Required'],
            [412, 'Precondition Failed'],
            [413, 'Request Entity Too Large'],
            [414, 'Request-URI Too Long'],
            [415, 'Unsupported Media Type'],
            [416, 'Requested Range Not Satisfiable'],
            [417, 'Expectation Failed'],
            [500, 'Internal Server Error'],
            [501, 'Not Implemented'],
            [502, 'Bad Gateway'],
            [503, 'Service Unavailable'],
            [504, 'Gateway Timeout'],
            [505, 'HTTP Version Not Supported']
        ];
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
     * @expectedException \InvalidArgumentException
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
