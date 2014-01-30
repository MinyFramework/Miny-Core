<?php

namespace Miny\Factory;

/**
 * Test class for Blueprint.
 * Generated by PHPUnit on 2012-08-02 at 23:43:56.
 */
class BlueprintTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Blueprint
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Blueprint('SomeClass');
    }

    /**
     * @covers Miny\Factory\Blueprint::setArguments
     * @covers Miny\Factory\Blueprint::getArguments
     */
    public function testArguments()
    {
        $this->object->setArguments('a', 'b', 'c');
        $this->assertEquals($this->object->getArguments(), array('a', 'b', 'c'));
    }

    /**
     * @covers Miny\Factory\Blueprint::setParent
     * @covers Miny\Factory\Blueprint::hasParent
     * @covers Miny\Factory\Blueprint::getParent
     */
    public function testParent()
    {
        $this->assertFalse($this->object->hasParent());
        $this->object->setParent('parent');
        $this->assertTrue($this->object->hasParent());
        $this->assertEquals($this->object->getParent(), 'parent');
    }

    /**
     * @covers Miny\Factory\Blueprint::addMethodCall
     * @covers Miny\Factory\Blueprint::getMethodCalls
     */
    public function testMethodCalls()
    {
        $this->object->addMethodCall('method_a', 'arg_1', 'arg_2');
        $this->object->addMethodCall('method_b', 'arg_1');
        $this->assertEquals(array(
            array('method_a', array('arg_1', 'arg_2')),
            array('method_b', array('arg_1'))
        ), $this->object->getMethodCalls());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Blueprint argument must be string or a Closure.
     */
    public function testConstructorException()
    {
        new Blueprint(4);
    }

    /**
     * @covers Miny\Factory\Blueprint::setProperty
     * @covers Miny\Factory\Blueprint::getProperties
     */
    public function testProperties()
    {
        $array = array(
            'property_a' => 'value_a',
            'property_b' => 'value_b',
        );
        foreach ($array as $key => $value) {
            $this->object->setProperty($key, $value);
        }
        $this->assertEquals($array, $this->object->getProperties());
    }

    /**
     * @covers Miny\Factory\Blueprint::getClassName
     */
    public function testGetClassName()
    {
        $this->assertEquals($this->object->getClassName(), 'SomeClass');
    }

    /**
     * @covers Miny\Factory\Blueprint::isSingleton
     */
    public function testIsSingleton()
    {
        $this->assertTrue($this->object->isSingleton());
        $object = new Blueprint('SomeClass', false);
        $this->assertFalse($object->isSingleton());
    }

    public function testCallbacks()
    {
        $this->assertEmpty($this->object->getCallbacks());
        $this->object->addCallback(function () {
        }, 'param', 2, 3);
        $callbacks = $this->object->getCallbacks();
        $this->assertNotEmpty($callbacks);
        $this->assertInstanceOf('\Closure', $callbacks[0][0]);
        $this->assertEquals(array('param', 2, 3), $callbacks[0][1]);
    }
}

?>
