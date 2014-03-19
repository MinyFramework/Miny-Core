<?php

namespace Miny\Shutdown;

use PHPUnit_Framework_TestCase;

class ShutdownServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatExceptionIsThrownWhenACallbackIsNotCallable()
    {
        $shutdown = new ShutdownService();
        $shutdown->register('not a callback');
    }
}
