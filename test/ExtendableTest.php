<?php

namespace Miny;

use BadMethodCallException;
use InvalidArgumentException;

class FooPlugin
{

    public function bar()
    {
        return 'bar';
    }

    public function baz()
    {
        return 'baz';
    }

    public function foobar()
    {
        return 'foobar';
    }
}

class ExtendableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Extendable
     */
    protected $object;

    protected function setUp()
    {
        $object = new Extendable;
        $object->addMethod(
            'foo',
            function () {
                return 'foo';
            }
        );

        $object->addMethods(
            new FooPlugin,
            [
                'bar',
                'baz'
            ]
        );

        $this->object = $object;
    }

    public function testCall()
    {
        $this->assertEquals('foo', $this->object->foo());
        $this->assertEquals('bar', $this->object->bar());
        $this->assertEquals('baz', $this->object->baz());
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testCallNotFoundException()
    {
        $this->object->foobar();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCallBadTypeException()
    {
        $this->object->__call(5, []);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddMethodNameException()
    {
        $this->object->addMethod(5, null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddMethodCallbackException()
    {
        $this->object->addMethod('method', null);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddMethodsObjectException()
    {
        $this->object->addMethods('object', []);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddMethodsMethodException()
    {
        $this->object->addMethods(new \stdClass, ['foo']);
    }

    public function testSetterException()
    {
        $this->object->addSetter('foo');
        $this->object->addSetter('asd', 'setAsdMethod');
        $this->object->addSetters(['foobar' => 'setFooBarMethod', 'baz']);

        $this->object->setFoo('bar');
        $this->object->setAsdMethod('bar');
        $this->object->setFooBarMethod('bar');
        $this->object->setBaz('bar');

        $this->assertEquals('bar', $this->object->foo);
        $this->assertEquals('bar', $this->object->asd);
        $this->assertEquals('bar', $this->object->baz);
        $this->assertEquals('bar', $this->object->foobar);
    }
}
