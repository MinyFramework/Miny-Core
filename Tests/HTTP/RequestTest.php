<?php

namespace Miny\HTTP;

class RequestTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $_GET                      = array(
            'get_array'
        );
        $_COOKIE                   = array(
            'cookie_array'
        );
        $_SERVER['REQUEST_URI']    = '/some_path/to?this=foo';
        $_SERVER['REMOTE_ADDR']    = 'my_ip';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testGetGlobal()
    {
        $request = Request::getGlobal();
        $this->assertInstanceOf(__NAMESPACE__ . '\Request', $request);
        return $request;
    }

    /**
     *
     * @depends testGetGlobal
     */
    public function test__get(Request $request)
    {
        $this->assertEquals($_GET, $request->get);
        $this->assertEmpty($request->post);
        $this->assertEquals($_COOKIE, $request->cookie);
        $this->assertEquals('GET', $request->method);
        $this->assertEquals('my_ip', $request->ip);
        $this->assertEquals('/some_path/to', $request->path);
    }

    public function testIsSubRequest()
    {
        $this->assertFalse(Request::getGlobal()->isSubRequest());
        $sub = Request::getGlobal()->getSubRequest('', '');
        $this->assertTrue($sub->isSubRequest());
    }
}

?>
