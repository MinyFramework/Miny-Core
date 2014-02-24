<?php

namespace Miny\HTTP;

use InvalidArgumentException;
use OutOfBoundsException;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $_GET                      = array(
            'key' => 'value',
            'get_array'
        );
        $_COOKIE                   = array(
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

    public function testIsSubRequest()
    {
        $this->assertFalse(Request::getGlobal()->isSubRequest());
        $sub = Request::getGlobal()->getSubRequest('', '');
        $this->assertTrue($sub->isSubRequest());
    }

    public function testIsAjax()
    {
        $this->assertFalse(Request::getGlobal()->isAjax());

        $request = new Request('method', 'url');
        $request->getHeaders()->set('x-requested-with', 'xmlhttprequest');

        $this->assertTrue($request->isAjax());
    }

    public function testExtractingHeaders()
    {
        $this->assertEquals('value', Request::getGlobal()->getHeaders()->get('something'));
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
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Key foo is not set.
     */
    public function testGetInvalidKey()
    {
        Request::getGlobal()->get('foo');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage $key must be a string.
     */
    public function testCallWithInvalidMethod()
    {
        Request::getGlobal()->get(5);
    }

    public function testCall()
    {
        $request = Request::getGlobal();
        $this->assertEquals('value', $request->get('key'));
        $this->assertEquals('bar', $request->get('foo', 'bar'));
    }
}

?>
