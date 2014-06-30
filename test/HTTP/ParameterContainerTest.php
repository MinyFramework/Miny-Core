<?php

namespace Miny\HTTP;

class ParameterContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ParameterContainer
     */
    private $container;

    public function setUp()
    {
        $this->container = new ParameterContainer(array(
            'foo' => 'bar'
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatSetRequiresAStringArgument()
    {
        $this->container->set(5, 'anything');
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testThatGetThrowsExceptionWhenKeyIsNotFoundAndDefaultIsNull()
    {
        $this->container->get('anything not foo');
    }

    public function testThatAddAddsValuesToTheContainer()
    {
        $this->assertFalse($this->container->has('bar'));

        $this->container->add(array('bar' => 'baz'));

        $this->assertTrue($this->container->has('bar'));
    }

    public function testThatAddAddsOverridesValues()
    {
        $this->assertEquals('bar', $this->container->get('foo'));

        $this->container->add(array('foo' => 'baz'));

        $this->assertEquals('baz', $this->container->get('foo'));
    }

    public function testThatGetReturnsDefaultWhenKeyIsNotFound()
    {
        $this->assertEquals('default', $this->container->get('foobar', 'default'));
    }

    public function testThatSetAddsAValue()
    {
        $this->assertFalse($this->container->has('bar'));
        $this->container->set('bar', 'baz');
        $this->assertTrue($this->container->has('bar'));
        $this->assertEquals('baz', $this->container->get('bar'));
    }

    public function testThatRemoveRemovesAValue()
    {
        $this->assertTrue($this->container->has('foo'));
        $this->container->remove('foo');
        $this->assertFalse($this->container->has('foo'));
    }

    public function testThatToArrayReturnsAllStoredData()
    {
        $this->container->add(array('bar' => 'baz'));
        $this->container->set('foobar', 'foobaz');
        $this->assertEquals(
            array(
                'foo'    => 'bar',
                'bar'    => 'baz',
                'foobar' => 'foobaz'
            ),
            $this->container->toArray()
        );
    }
}
