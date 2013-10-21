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

    public function testIssetParameters()
    {
        $this->assertTrue(isset($this->object['array_a']['param_b']));
        $this->assertTrue(isset($this->object['array_a:param_b']));
        $this->assertFalse(isset($this->object['array_a:param_not_exists']));
    }

    public function testGetParameters()
    {
        $array_a_with_resolved_links = array(
            'param_b' => 'value_c',
            'array_b' => array(
                'value_c'        => 'value_b_value',
                'deep_parameter' => 'deep_value'
            )
        );

        //simply get the item
        $this->assertEquals('value_c', $this->object['value_b']);

        //get the item from a deeper level
        $this->assertEquals('value_c', $this->object['array_a:param_b']);

        //should resolve links in array key and value, recursively
        $this->assertEquals($array_a_with_resolved_links, $this->object['array_a']);
        $this->assertEquals($array_a_with_resolved_links['array_b'], $this->object['array_a:array_b']);

        //resolves a link that points to a link in a deeper level
        $this->assertEquals('value_c', $this->object['param_c']);

        //leave value untouched if it can't be resolved
        $this->assertEquals('{@not_exists}', $this->object['invalid_link']);
    }

    public function testSetParameters()
    {
        //overwrite
        $this->object['value_b'] = 'value_b';
        $this->assertEquals('value_b', $this->object['value_b']);

        //links should also change
        $this->assertEquals('value_b', $this->object['param_c']);

        //new item
        $this->assertFalse(isset($this->object['param_d']));
        $this->object['param_d'] = 'value_d';
        $this->assertTrue(isset($this->object['param_d']));
        $this->assertEquals('value_d', $this->object['param_d']);
    }

    public function testUnsetParameters()
    {
        //simple unset
        $this->assertTrue(isset($this->object['some_item']));

        unset($this->object['some_item']);
        $this->assertFalse(isset($this->object['some_item']));

        unset($this->object['array_a:array_b:deep_parameter']);
        $this->assertEquals(array(
            'value_c' => 'value_b_value'
                ), $this->object['array_a:array_b']);

        //deleting a link
        unset($this->object['value_b']);
        $this->assertEquals('{@value_b}', $this->object['array_a:param_b']);

        //tricky case - don't delete keys with same name, only the one with the correct path
        $tricky_result = array('array' => array());

        unset($this->object['array:array:array']);
        $this->assertTrue(isset($this->object['array']));
        $this->assertTrue(isset($this->object['array:array']));
        $this->assertFalse(isset($this->object['array:array:array']));
        $this->assertEquals($tricky_result, $this->object['array']);
    }

    public function testParameterMerge()
    {
        $new_parameters = array(
            'array_a' => array(
                'param_b'          => 'some_value', //overwrite
                'additional_param' => 'other_value' //new key
            )
        );
        $expected_result = array(
            'array_a'      => array(
                'param_b'          => 'some_value',
                'array_b'          => array(
                    '{@param_c}'     => 'value_b_value',
                    'deep_parameter' => 'deep_value'
                ),
                'additional_param' => 'other_value'
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
        $expected_result_resolved = array(
            'array_a'      => array(
                'param_b'          => 'some_value',
                'array_b'          => array(
                    'some_value'     => 'value_b_value',
                    'deep_parameter' => 'deep_value'
                ),
                'additional_param' => 'other_value'
            ),
            'param_c'      => 'some_value',
            'value_b'      => 'value_c',
            'invalid_link' => '{@not_exists}',
            'some_item'    => 'some_value',
            'array'        => array(
                'array' => array(
                    'array' => 'value'
                )
            )
        );
        $this->object->setParameters($new_parameters);
        $this->assertEquals($expected_result, $this->object->getParameters());
        $this->assertEquals($expected_result_resolved, $this->object->getResolvedParameters());
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
        $this->object->setParameters(array(
            'link' => 'helper'
        ));

        $this->object->add('singleton', __NAMESPACE__ . '\TestClass')
                ->setArguments('literal', '@param_c', '&helper')
                ->setProperty('prop_a', 'literal')
                ->setProperty('prop_b', '@param_c')
                ->setProperty('prop_c', '&helper')
                ->setProperty('prop_d', '&{@link}')
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
            'prop_d' => $helper
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
