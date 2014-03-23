<?php

namespace Miny\Factory;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    private $container;

    public function setUp()
    {
        $this->container = new Container;
    }

    public function testThatAliasIsSet()
    {
        $this->container->addAlias('foobar', 'FooClass');
        $this->assertEquals('FooClass', $this->container->getAlias('foobar'));
    }

    public function testThatAliasSetsConstructorArguments()
    {
        $this->container->addAlias('foobar', 'FooClass', array('a', 'b'));
        $this->assertEquals(array('a', 'b'), $this->container->getConstructorArguments('foobar'));
    }

    public function testThatConstructorArgumentsAreSet()
    {
        $this->container->addConstructorArguments('fooBar', 'a', 'b');
        $this->assertEquals(array('a', 'b'), $this->container->getConstructorArguments('fooBar'));
    }

    public function testThatEmptyConstructorArgumentsIsReturned()
    {
        $this->assertEquals(array(), $this->container->getConstructorArguments(42));
        $this->assertEquals(array(), $this->container->getConstructorArguments('fooBar'));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testThatExceptionIsThrownWhenAliasNotSet()
    {
        $this->container->getAlias('foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatSetInstanceThrowsException()
    {
        $this->container->setInstance(42);
    }

    public function testThatContainerContainsItself()
    {
        $container = $this->container->get('\\Miny\\Factory\\Container');

        $this->assertSame($container, $this->container);
    }

    public function testThatForceNewCreatesSeparateInstance()
    {
        $container = $this->container->get('\\Miny\\Factory\\Container', array(), true);

        $this->assertNotSame($container, $this->container);
    }

    public function testThatForceNewDoesNotStoreCreatedInstance()
    {
        $container = $this->container->get('\\Miny\\Factory\\Container', array(), true);
        $stored = $this->container->get('\\Miny\\Factory\\Container');

        $this->assertSame($stored, $this->container);
        $this->assertNotSame($container, $stored);
        $this->assertNotSame($container, $this->container);
    }
}
