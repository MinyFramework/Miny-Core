<?php

namespace Miny\Test\Shutdown;

use Miny\Shutdown\ShutdownService;
use PHPUnit_Framework_TestCase;

class MockShutdownService extends ShutdownService
{
    public function __construct()
    {
    }
}

class ShutdownServiceTest extends PHPUnit_Framework_TestCase
{
    /***
     * @var ShutdownService
     */
    private $shutdown;

    public function setUp()
    {
        $this->shutdown = new MockShutdownService();
    }

    public function testCallbacksWithoutPriorityAreCalledInOrder()
    {
        $out = '';
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '1';
            }
        );
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '2';
            }
        );
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '3';
            }
        );
        $this->shutdown->callShutdownFunctions();
        $this->assertEquals('123', $out);
    }

    public function testCallbacksAreCalledOnOrderOfPriority()
    {
        $out = '';
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '1';
            },
            2
        );
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '2';
            },
            1
        );
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '3';
            },
            0
        );
        $this->shutdown->callShutdownFunctions();
        $this->assertEquals('321', $out);
    }

    public function testCallbacksOnSamePriorityCalledInOrderOfRegistration()
    {
        $out = '';
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '1';
            },
            2
        );
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '2';
            },
            1
        );
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '3';
            },
            1
        );
        $this->shutdown->callShutdownFunctions();
        $this->assertEquals('231', $out);
    }

    public function testAutoPriorityIsAlwaysOneLowerThanTheLast()
    {
        $out = '';
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '1';
            },
            2
        );
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '2';
            },
            4
        );
        $this->shutdown->register(
            function () use (&$out) {
                $out .= '3';
            }
        );
        $this->shutdown->callShutdownFunctions();
        $this->assertEquals('123', $out);
    }
}
