<?php

namespace Miny\Factory;

class TestClass
{
    private $constructor;
    private $method   = array();
    private $property = array();

    public function __construct()
    {
        $this->constructor = func_get_args();
    }

    public function __call($method, $args)
    {
        $this->method[$method] = $args;
    }

    public function __set($prop, $value)
    {
        $this->property[$prop] = $value;
    }

    public function __get($key)
    {
        return $this->$key;
    }
}

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Factory
     */
    protected $object;
    protected $parameters;

    protected function setUp()
    {
        $this->parameters = $this->getMock('\Miny\Factory\ParameterContainer',
                array('resolveLinks', 'offsetGet', 'offsetSet', 'offsetExists', 'offsetUnset'));

        $this->parameters->expects($this->any())
                ->method('offsetGet')
                ->will($this->returnArgument(0));

        $this->parameters->expects($this->any())
                ->method('resolveLinks')
                ->will($this->returnArgument(0));

        $this->object = new Factory($this->parameters);
    }

    public function testGetParameters()
    {
        $this->assertInstanceOf('\Miny\Factory\ParameterContainer', $this->object->getParameters());
        $factory = new Factory;
        $this->assertInstanceOf('\Miny\Factory\ParameterContainer', $factory->getParameters());
    }

    public function testArrayAccessInterface()
    {
        $this->parameters->expects($this->once())
                ->method('offsetExists')
                ->with($this->equalTo('something'))
                ->will($this->returnValue(true));

        $this->parameters->expects($this->once())
                ->method('offsetUnset')
                ->with($this->equalTo('something'));

        $this->parameters->expects($this->once())
                ->method('offsetSet')
                ->with($this->equalTo('something'), $this->equalTo('value'));

        // The mock verifies that these get called once.
        $this->assertTrue(isset($this->object['something']));
        $this->object['something'] = 'value';
        unset($this->object['something']);

        $this->assertEquals('something', $this->object['something']);
    }

    public function testAliasses()
    {
        $this->object->main = new \stdClass;
        $this->object->name = '\stdClass';

        $this->object->addAlias('alias', 'main');
        $this->object->addAlias('other', 'name');

        $this->assertTrue(isset($this->object->alias));
        $this->assertTrue(isset($this->object->other));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Factory::__set expects a string or an object.
     */
    public function testSetException()
    {
        $this->object->something = 4;
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Factory::setInstance needs an object for alias something
     */
    public function testSetInstanceException()
    {
        $this->object->setInstance('something', 4);
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Blueprint not found: something
     */
    public function testGetBlueprintException()
    {
        $this->object->getBlueprint('something');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Class not found: barClass
     */
    public function testInstantiateClassNotFoundException()
    {
        $this->object->add('foo', 'barClass');
        $this->object->foo;
    }

    public function testSetAndGetBlueprint()
    {
        $this->assertEmpty($this->object->getBlueprints());
        $this->object->foo = 'bar';
        $this->object->bar = new Blueprint('baz');
        $this->object->baz = function() {

        };
        $this->assertNotEmpty($this->object->getBlueprints());
        $this->assertInstanceOf('\Miny\Factory\Blueprint', $this->object->getBlueprint('foo'));
        $this->assertInstanceOf('\Miny\Factory\Blueprint', $this->object->getBlueprint('bar'));
        $this->assertInstanceOf('\Miny\Factory\Blueprint', $this->object->getBlueprint('baz'));
    }

    public function testGetObject()
    {
        $class = new \stdClass;

        $this->object->foo = $class;
        $this->object->bar = function() use($class) {
            return $class;
        };
        $this->object->add('singleton', '\stdClass');
        $this->object->add('non_singleton', '\stdClass', false);
        $this->object->add('link', '@\stdClass');

        $this->assertSame($class, $this->object->foo);
        $this->assertSame($class, $this->object->bar);

        $this->assertInstanceOf('\stdClass', $this->object->singleton);
        $this->assertInstanceOf('\stdClass', $this->object->non_singleton);
        $this->assertInstanceOf('\stdClass', $this->object->link);

        $this->assertSame($this->object->singleton, $this->object->singleton);
        $this->assertNotSame($this->object->non_singleton, $this->object->non_singleton);
    }

    public function testInjectingDependencies()
    {
        $class = new \stdClass;

        $this->object->foo = $class;

        $this->object->add('bar', __NAMESPACE__ . '\TestClass')
                ->setArguments('a', 'b')
                ->addMethodCall('foo', 'foo_value')
                ->addMethodCall('bar', 'bar_1', 'bar_2')
                ->setProperty('a', '&foo')
                ->setProperty('b', '@param')
                ->setProperty('c', '\@param');

        $this->object->add('baz', __NAMESPACE__ . '\TestClass')
                ->setParent('bar');

        $this->object->add('foobar', __NAMESPACE__ . '\TestClass')
                ->setParent('bar')
                ->setArguments('c')
                ->setProperty('foobar_blueprint', new Blueprint('\stdClass'))
                ->addMethodCall('foobar_array', '&bar->constructor')
                ->addMethodCall('foobar_callable', '*bar::property')
                ->addMethodCall('foobar_method_call', '&bar::__get::constructor')
                ->setProperty('resolved', '{@param}');

        foreach (array('bar', 'baz') as $name) {
            $obj = $this->object->$name;
            $this->assertContains('a', $obj->constructor);
            $this->assertContains('b', $obj->constructor);
            $this->assertContains('foo_value', $obj->method['foo']);
            $this->assertContains('bar_1', $obj->method['bar']);
            $this->assertContains('bar_2', $obj->method['bar']);
            $this->assertSame($class, $obj->property['a']);
            $this->assertEquals('param', $obj->property['b']);
            $this->assertEquals('@param', $obj->property['c']);
        }

        $foobar = $this->object->foobar;

        $this->assertContains('c', $foobar->constructor);
        $this->assertNotContains('a', $foobar->constructor);

        $this->assertContains('a', $foobar->method['foobar_array'][0]);
        $this->assertContains('b', $foobar->method['foobar_array'][0]);

        $this->assertContains('a', $foobar->method['foobar_method_call'][0]);
        $this->assertContains('b', $foobar->method['foobar_method_call'][0]);

        $this->assertTrue(is_callable($foobar->method['foobar_callable'][0]));
        $this->assertInstanceOf('\stdClass', $foobar->property['foobar_blueprint']);
        $this->assertEquals('{@param}', $foobar->property['resolved']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Class "foo" does not have a method "bar"
     */
    public function testMethodCallException()
    {
        $this->object->foo = new \stdClass;

        $this->object->add('bar', '\stdClass')
                ->setProperty('foo', '&foo::bar::baz');
        $this->object->bar;
    }

    public function testReplace()
    {
        $this->object->foo = '\stdClass';
        $old = $this->object->replace('foo', null);

        $this->assertNull($old);

        $object = $this->object->replace('foo', new \stdClass);
        $this->assertNotSame($object, $this->object->foo);
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Blueprint not found: foo
     */
    public function testReplaceExceptionWithoutBlueprint()
    {
        $this->object->foo = new \stdClass;
        $this->object->replace('foo', null);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Can only insert objects
     */
    public function testReplaceExceptionWrongType()
    {
        $this->object->foo = new \stdClass;
        $this->object->replace('foo', 5);
    }

    public function testSetInstance()
    {
        // Should not throw Exception.
        $this->object->setInstance('foo', new \stdClass);
    }
}

?>