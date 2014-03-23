<?php

namespace Miny\HTTP;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $_GET    = array(
            'key' => 'value',
            'get_array'
        );
        $_COOKIE = array(
            'cookie_array'
        );

        $_SERVER['REQUEST_URI']    = '/some_path/to?this=foo';
        $_SERVER['REMOTE_ADDR']    = 'my_ip';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_SOMETHING'] = 'value';
    }

    public function testGetGlobal()
    {
        $request = Request::getGlobal();
        $this->assertInstanceOf(__NAMESPACE__ . '\Request', $request);

        return $request;
    }

    /**
     * @depends testGetGlobal
     */
    public function testIsSubRequest(Request $request)
    {
        $this->assertFalse($request->isSubRequest());
        $sub = $request->getSubRequest('', '');
        $this->assertTrue($sub->isSubRequest());
    }

    /**
     * @depends testGetGlobal
     */
    public function testIsAjax(Request $request)
    {
        $this->assertFalse($request->isAjax());

        $request = new Request('method', 'url');
        $request->getHeaders()->set('x-requested-with', 'xmlhttprequest');

        $this->assertTrue($request->isAjax());
    }

    /**
     * @depends testGetGlobal
     */
    public function testExtractingHeaders(Request $request)
    {
        $this->assertEquals('value', $request->getHeaders()->get('something'));
    }

    public function testEmulatedMethod()
    {
        $_POST = array('_method' => 'boo');
        $this->assertEquals('BOO', Request::getGlobal()->getMethod());
    }

    public function testForwardedHeader()
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'new_ip';
        $this->assertEquals('new_ip', Request::getGlobal()->getIp());
    }

    /**
     * @depends testGetGlobal
     */
    public function testCall(Request $request)
    {
        $this->assertInstanceOf('\\Miny\\HTTP\\ParameterContainer', $request->get());
        $this->assertInstanceOf('\\Miny\\HTTP\\ParameterContainer', $request->post());
        $this->assertInstanceOf('\\Miny\\HTTP\\ParameterContainer', $request->cookie());
        $this->assertEquals('value', $request->get()->get('key'));
        $this->assertEquals('bar', $request->get()->get('foo', 'bar'));
    }
}

?>
