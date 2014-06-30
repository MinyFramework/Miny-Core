<?php

namespace Miny\HTTP;

class ReferenceParameterContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ReferenceParameterContainer
     */
    private $container;
    private $data;

    public function setUp()
    {
        $this->data      = array(
            'foo' => 'bar'
        );
        $this->container = new ReferenceParameterContainer($this->data);
    }

    public function testThatDataIsStoredAsReference()
    {
        $value =& $this->container->get('foo');

        $this->assertEquals('bar', $value);

        $value = 'baz';

        $this->assertEquals('baz', $this->container->get('foo'));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testThatGetThrowsExceptionWhenKeyIsNotFoundAndDefaultIsNull()
    {
        $this->container->get('anything not foo');
    }

    public function testThatGetReturnsDefaultWhenKeyIsNotFound()
    {
        $this->assertEquals('default', $this->container->get('foobar', 'default'));
    }
}
