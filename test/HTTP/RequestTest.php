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
        $request->getHeaders()->set('x-requested-with', 'XMLHttpRequest');

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
    public function testGetters(Request $request)
    {
        $this->assertInstanceOf('\\Miny\\HTTP\\ParameterContainer', $request->get());
        $this->assertInstanceOf('\\Miny\\HTTP\\ParameterContainer', $request->post());
        $this->assertInstanceOf('\\Miny\\HTTP\\ParameterContainer', $request->cookie());
        $this->assertEquals('value', $request->get()->get('key'));
        $this->assertEquals('bar', $request->get()->get('foo', 'bar'));
    }

    public function testSubRequest()
    {
        $_POST   = array('key' => 'data');
        $request = Request::getGlobal();
        $this->assertTrue($request->post()->has('key'));

        $subRequest = $request->getSubRequest('GET', 'subrequest uri');

        $this->assertSame($request->post(), $subRequest->post());

        $subRequestWithPost = $request->getSubRequest('GET', '', array('key' => 'other data'));

        $this->assertNotSame($request->post(), $subRequestWithPost->post());
        $this->assertInstanceOf('\\Miny\\HTTP\\ParameterContainer', $subRequestWithPost->post());
        $this->assertTrue($subRequestWithPost->post()->has('key'));
        $this->assertEquals('other data', $subRequestWithPost->post()->get('key'));

        $this->assertEquals('subrequest uri', $subRequest->getUrl());
        $this->assertEquals('GET', $subRequest->getMethod());
    }

    public function testGetUriAndPath()
    {
        $request = Request::getGlobal();

        $this->assertEquals('/some_path/to?this=foo', $request->getUrl());
        $this->assertEquals('/some_path/to', $request->getPath());
    }

    public function testGlobalsAreReferences()
    {
        $request = Request::getGlobal();

        $request->get()->set('key', 'not_value');
        $this->assertEquals('not_value', $_GET['key']);
    }
}
