<?php

namespace Miny\HTTP;

use Miny\Utils\ArrayReferenceWrapper;

class SessionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @runInSeparateProcess
     */
    public function testFlashVariables()
    {
        $data = [];

        $session = new Session(false);
        $session->open(new ArrayReferenceWrapper($data));

        $session->foo = 'bar';
        $this->assertTrue(isset($session->foo));
        $this->assertEquals('bar', $session->foo);

        $session->close();
        $session->open(null);

        $this->assertTrue(isset($session->foo));
        $this->assertEquals('bar', $session->foo);

        $session->close();
        $session->open(null);

        $this->assertFalse(isset($session->foo));
    }
}