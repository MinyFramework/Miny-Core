<?php

namespace Miny\Factory;

use OutOfBoundsException;

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

    public function testAlias()
    {
        $this->container->addAlias('foobar', 'FooClass', array('a', 'b'));
        $this->assertEquals('FooClass', $this->container->getAlias('foobar'));
        $this->assertEquals(array('a', 'b'), $this->container->getConstructorArguments('foobar'));
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testExceptionWhenAliasNotSet()
    {
        $this->container->getAlias('foobar');
    }
}
