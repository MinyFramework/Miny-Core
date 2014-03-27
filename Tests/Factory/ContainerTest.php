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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThatAddCallbackThrowsException()
    {
        $this->container->addCallback('foo', 42);
    }

    public function testThatSetInstanceSetsTheClassName()
    {
        $class = new \stdClass;
        $this->container->setInstance($class);
        $this->assertSame($class, $this->container->get('\stdClass'));
    }

    public function testThatSetInstanceReturnsTheOldObject()
    {
        $class = new \stdClass;
        $this->container->setInstance($class);
        $this->assertSame($class, $this->container->setInstance(new \stdClass()));
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
        $stored    = $this->container->get('\\Miny\\Factory\\Container');

        $this->assertSame($stored, $this->container);
        $this->assertNotSame($container, $stored);
        $this->assertNotSame($container, $this->container);
    }

    public function testThatCallbacksAreCalled()
    {
        $called1 = false;
        $called2 = false;

        $this->container->addCallback(
            '\stdClass',
            function () use (&$called1) {
                $called1 = true;
            }
        );
        $this->container->addCallback(
            '\stdClass',
            function () use (&$called2) {
                $called2 = true;
            }
        );

        $this->container->get('\stdClass');

        $this->assertTrue($called1);
        $this->assertTrue($called2);
    }

    public function testThatClosureAliasCanBeInstantiated()
    {
        $this->container->addAlias(
            'foo',
            function (Container $c, array $params) {
                //arguments here test (enforce) that they are passed to the closure
                return new \stdClass();
            }
        );

        $this->assertInstanceOf('\stdClass', $this->container->get('foo'));
    }
}
