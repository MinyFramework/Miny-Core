<?php

namespace Miny\Test\HTTP;

use Miny\HTTP\FlashVariableStorage;

class FlashVariableStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testFlashVariables()
    {
        $data      = [];
        $container = new FlashVariableStorage($data);
        $container->set('foo', 'bar', 0);
        $container->set('foobar', 'baz', 1);
        $container->set('baz', 'baz', 1);

        $this->assertTrue($container->has('baz'));
        $container->remove('baz');
        $this->assertFalse($container->has('baz'));

        $this->assertTrue($container->has('foo'));
        $this->assertTrue($container->has('foobar'));
        $this->assertFalse($container->has('bar'));
        $this->assertEquals('bar', $container->get('foo'));
        $this->assertEquals('baz', $container->get('foobar'));

        $container = new FlashVariableStorage($data);
        $container->decrement();

        $this->assertFalse($container->has('foo'));
        $this->assertFalse($container->has('bar'));
        $this->assertTrue($container->has('foobar'));
        $this->assertEquals('baz', $container->get('foobar'));
    }
}
