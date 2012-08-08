<?php

namespace Miny\Factory;

require_once dirname(__FILE__) . '/../../Factory/Blueprint.php';
require_once dirname(__FILE__) . '/../../Factory/Factory.php';

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $object;
    protected $parameters = array(
        'array_a' => array(
            'param_b' => '{@value_b}',
            'array_b' => array(
                '{@param_c}'     => 'value_b_value',
                'deep_parameter' => 'deep_value'
            ),
        ),
        'param_c'        => '{@array_a:param_b}',
        'value_b'        => 'value_c',
        'invalid_link'   => '{@not_exists}',
        'some_item'      => 'some_value',
        'array'          => array(
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

}

?>
