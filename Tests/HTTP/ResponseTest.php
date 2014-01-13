<?php

namespace Miny\HTTP;

require_once dirname(__FILE__) . '/../../HTTP/Response.php';

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    protected $response;

    public function setUp()
    {
        $this->response = new Response;
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
    public function testResponseShouldReturnStatusStringsForValidStatusCodes($code, $message)
    {
        $this->response->setCode($code);
        $this->assertEquals($message, $this->response->getStatus());
    }
}

?>
