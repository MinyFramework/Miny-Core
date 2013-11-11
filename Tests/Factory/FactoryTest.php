<?php

namespace Miny\Factory;

require_once dirname(__FILE__) . '/../../Factory/Blueprint.php';
require_once dirname(__FILE__) . '/../../Factory/Factory.php';

class TestClass
{
    private $constructor;
    private $method = array();
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

class TestHelperClass
{
    public $property = 'property';

    public function method($return = 'method')
    {
        return $return;
    }

}

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    protected $parameters = array(
        'array_a'      => array(
            'param_b' => '{@value_b}',
            'array_b' => array(
                '{@param_c}'     => 'value_b_value',
                'deep_parameter' => 'deep_value'
            ),
        ),
        'param_c'      => '{@array_a:param_b}',
        'value_b'      => 'value_c',
        'invalid_link' => '{@not_exists}',
        'some_item'    => 'some_value',
        'array'        => array(
            'array' => array(
                'array' => 'value'
            )
        )
    );

    protected function setUp()
    {
        $this->object = new Factory($this->parameters);
    }

    public function testSetter()
    {
        $std = new \stdClass;
        $blueprint = new Blueprint('\stdClass');

        //testing setInstance
        $this->object->setInstance('std_name', $std);
        $this->assertSame($std, $this->object->std_name);

        //setter should call setInstance
        $this->object->std = $std;
        $this->assertSame($std, $this->object->std);

        //testing register
        $this->assertSame($blueprint, $this->object->register('some_name', $blueprint));
        $this->assertInstanceOf('\stdClass', $this->object->some_name);

        //setter should call register for Blueprints
        $this->object->bp = $blueprint;
        $this->assertInstanceOf('\stdClass', $this->object->bp);

        //register should remove old instance
        $this->object->std = $blueprint;
        $this->assertNotSame($std, $this->object->std);
    }

    public function testCreateSingleton()
    {
        $helper = new TestHelperClass;

        $this->object->helper = $helper;
        $this->object->getParameters()->addParameters(array(
            'link' => 'helper',
            'link_to_instance_reference' => '&helper'
        ));

        $this->object->add('singleton', __NAMESPACE__ . '\TestClass')
                ->setArguments('literal', '@param_c', '&helper')
                ->setProperty('prop_a', 'literal')
                ->setProperty('prop_b', '@param_c')
                ->setProperty('prop_c', '&helper')
                ->setProperty('prop_d', '&{@link}')
                ->setProperty('prop_e', '@link_to_instance_reference')
                ->setProperty('prop_f', '{@link_to_instance_reference}')
                ->addMethodCall('method_a', 'literal_value', 'another_literal_value')
                ->addMethodCall('method_b', '@param_c', '@array:array:array')
                ->addMethodCall('method_c', '&helper', '&helper->property')
                ->addMethodCall('method_d', '*helper::method', '&helper::method::return')
                ->addMethodCall('method_e', '&helper::method::{@param_c}');

        $obj = $this->object->singleton;
        $this->assertSame($this->object->singleton, $this->object->singleton);

        $constructor_args = array('literal', 'value_c', $helper);

        $properties = array(
            'prop_a' => 'literal',
            'prop_b' => 'value_c',
            'prop_c' => $helper,
            'prop_d' => $helper,
            'prop_e' => $helper,
            'prop_f' => $helper
        );

        $method_calls = array(
            'method_a' => array('literal_value', 'another_literal_value'),
            'method_b' => array('value_c', 'value'),
            'method_c' => array($helper, $helper->property),
            'method_d' => array(array($helper, 'method'), $helper->method('return')),
            'method_e' => array('value_c'),
        );

        $this->assertEquals($constructor_args, $obj->constructor);
        $this->assertEquals($properties, $obj->property);
        $this->assertEquals($method_calls, $obj->method);
    }

    public function testCreateMultipleInstances()
    {
        $this->object->add('instance', __NAMESPACE__ . '\TestClass', false);
        $this->assertNotSame($this->object->instance, $this->object->instance);
    }

    public function testShouldInjectObjectConstructedFromBlueprint()
    {
        $this->object->add('object', __NAMESPACE__ . '\TestClass')
                ->addMethodCall('method_name', new Blueprint(__NAMESPACE__ . '\TestClass'));
        $this->assertInstanceOf(__NAMESPACE__ . '\TestClass', $this->object->object->method['method_name'][0]);
    }

}

?>