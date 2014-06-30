<?php

namespace Miny\HTTP;

use Miny\ArrayReferenceWrapper;

/**
 * @runTestsInSeparateProcesses
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Session
     */
    private $session;
    private $data;

    public function setUp()
    {
        $this->data = array();

        $this->session = new Session(false);
        $this->session->open(new ArrayReferenceWrapper($this->data));
    }

    public function testFlashVariablesCanBeAccessedInSameSession()
    {
        $this->session->foo = 'bar';
        $this->assertEquals('bar', $this->session->foo);
    }

    public function testFlashVariablesCanBeAccessedInNextSessionButNotAfter()
    {
        $this->session->foo = 'bar';

        $this->session->close();
        $this->session->open(null);

        $this->assertEquals('bar', $this->session->foo);

        $this->session->close();
        $this->session->open(null);

        $this->assertFalse(isset($this->session->foo));
    }
}
