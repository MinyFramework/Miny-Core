<?php

namespace Miny\Factory;

use Miny\Log\AbstractLog;

class TestClass
{
    private $parameters;

    public function __construct(\stdClass $object, $scalar, $default = 5)
    {
        $this->parameters = func_get_args();
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}

class ClassWithEmptyConstructor
{
    public function __construct()
    {
    }
}

class ClassWithAbstractArgumentType
{
    public function __construct(AbstractLog $foo)
    {
    }
}

class ClassWithNullableArgumentType
{
    public function __construct(AbstractLog $foo = null)
    {
    }
}

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    private $container;

    public function setUp()
    {
        $mock = $this->getMock(
            '\\Miny\\Factory\\LinkResolver',
            array('resolveReferences'),
            array(),
            'MockLinkResolver',
            false
        );
        $mock->expects($this->any())
            ->method('resolveReferences')
            ->will($this->returnArgument(0));

        $this->container = new Container($mock);
    }

    public function testThatAliasIsSet()
    {
        $this->container->addAlias('foobar', 'FooClass');
        $this->assertEquals('FooClass', $this->container->getAlias('foobar'));
    }

    public function testThatAliasIsInstantiated()
    {
        $this->container->addAlias('foobar', '\\stdClass');
        $this->assertInstanceOf('\\stdClass', $this->container->get('foobar'));
    }

    public function testThatConstructorArgumentsAreSet()
    {
        $this->container->addConstructorArguments('fooBar', 'a', 'b');
        $this->assertEquals(array('a', 'b'), $this->container->getConstructorArguments('fooBar'));
    }

    public function testThatEmptyConstructorArgumentsIsReturned()
    {
        $this->container->addAlias(
            'foo',
            function () {
            }
        );
        $this->assertEquals(array(), $this->container->getConstructorArguments(42));
        $this->assertEquals(array(), $this->container->getConstructorArguments('fooBar'));
        $this->assertEquals(array(), $this->container->getConstructorArguments('foo'));
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

    public function testContainerCanInstantiateClassesThatAreNotRegistered()
    {
        $this->assertInstanceOf('\\stdClass', $this->container->get('\\stdClass'));
    }

    public function testEmptyConstructor()
    {
        $this->container->get(__NAMESPACE__ . '\\ClassWithEmptyConstructor');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAbstractArgumentType()
    {
        $this->container->get(__NAMESPACE__ . '\\ClassWithAbstractArgumentType');
    }

    public function testNullableArgumentType()
    {
        $this->container->get(__NAMESPACE__ . '\\ClassWithNullableArgumentType');
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

    public function testClosureAliasCanBeInstantiated()
    {
        $test = $this;
        $this->container->addAlias(
            'foo',
            function (Container $c, array $params) use ($test) {
                $test->assertEquals(array(1, 2, 3), $params);

                return new \stdClass();
            }
        );

        //instantiate with default parameters
        $this->container->addConstructorArguments('foo', 1, 2, 3);
        $this->assertInstanceOf('\stdClass', $this->container->get('foo'));

        //instantiate with direct parameter injection
        $this->assertInstanceOf('\stdClass', $this->container->get('foo', array(1, 2, 3), true));
    }

    public function testObjectArgumentsAreAutomaticallyInstantiated()
    {
        $this->container->setConstructorArgument(__NAMESPACE__ . '\\TestClass', 1, 55);
        $obj = $this->container->get(__NAMESPACE__ . '\\TestClass');

        $params = $obj->getParameters();
        $this->assertInstanceOf('\stdClass', $params[0]);
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testContainerThrowsExceptionWhenDefaultArgumentIsNotSet()
    {
        $this->container->get(__NAMESPACE__ . '\\TestClass');
    }

    public function testDefaultArgumentsAreInjected()
    {
        //this is required because no default value is set in TestClass
        $obj = $this->container->get(__NAMESPACE__ . '\\TestClass', array(1 => 55));

        $params = $obj->getParameters();
        $this->assertEquals(5, $params[2]);
    }

    public function testDefaultArgumentsAreOverridden()
    {
        $class    = new \stdClass;
        $newClass = new \stdClass;
        $this->container->setInstance($class);

        $this->container->setConstructorArgument(__NAMESPACE__ . '\\TestClass', 0, $newClass);
        $this->container->setConstructorArgument(__NAMESPACE__ . '\\TestClass', 1, 55);
        $this->container->setConstructorArgument(__NAMESPACE__ . '\\TestClass', 2, 'asd');

        $obj = $this->container->get(__NAMESPACE__ . '\\TestClass');

        $params = $obj->getParameters();
        $this->assertNotSame($class, $params[0]);
        $this->assertSame($newClass, $params[0]);
        $this->assertEquals(55, $params[1]);
        $this->assertEquals('asd', $params[2]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAnAliasCanNotBeSetToItself()
    {
        $this->container->addAlias('\\stdClass', '\\stdClass');
        $this->container->get('\\stdClass');
    }
}
