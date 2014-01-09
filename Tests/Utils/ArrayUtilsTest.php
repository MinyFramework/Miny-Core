<?php

namespace Miny\Utils;

use OutOfBoundsException;
use PHPUnit_Framework_TestCase;

require_once dirname(__FILE__) . '/../../Utils/ArrayUtils.php';

class ArrayUtilsTest extends PHPUnit_Framework_TestCase
{
    private $array;

    protected function setUp()
    {
        $this->array = array(
            'key_a' => array(
                'subkey_a' => 'value_a'
            ),
            'key_b' => 'value'
        );
    }

    public function testExistsByPath()
    {
        $this->assertTrue(ArrayUtils::existsByPath($this->array, 'key_a:subkey_a'));
        $this->assertTrue(ArrayUtils::existsByPath($this->array, array('key_a', 'subkey_a')));
        $this->assertFalse(ArrayUtils::existsByPath($this->array, array('key_b:subkey_a')));
        $this->assertFalse(ArrayUtils::existsByPath($this->array, array('key_b', 'subkey_a')));
    }

    public function testGetByPath()
    {
        $this->assertEquals('value_a', ArrayUtils::getByPath($this->array, 'key_a:subkey_a'));
        $this->assertEquals('value_a', ArrayUtils::getByPath($this->array, array('key_a', 'subkey_a')));
        $this->assertEquals(null, ArrayUtils::getByPath($this->array, array('key_a', 'subkey_b')));
        $this->assertEquals('default', ArrayUtils::getByPath($this->array, array('key_a', 'subkey_b'), 'default'));
    }

    public function testFindByPath()
    {
        $this->assertEquals('value_a', ArrayUtils::findByPath($this->array, 'key_a:subkey_a'));
        $this->assertEquals('value_a', ArrayUtils::findByPath($this->array, array('key_a', 'subkey_a')));
        try {
            ArrayUtils::findByPath($this->array, array('key_a', 'subkey_b'));
            $this->fail('findByPath should throw an exception when a nonexistent item is requested.');
        } catch (OutOfBoundsException $e) {

        }
    }

    public function testSetByPath()
    {
        ArrayUtils::setByPath($this->array, 'key_a:subkey_a', 'new_value');
        ArrayUtils::setByPath($this->array, 'key_a:nonexistent:sub', 'value');
        $this->assertEquals('new_value', ArrayUtils::getByPath($this->array, 'key_a:subkey_a'));
        $this->assertEquals('value', ArrayUtils::getByPath($this->array, 'key_a:nonexistent:sub'));
    }

    public function testUnsetByPath()
    {
        ArrayUtils::unsetByPath($this->array, 'key_a:subkey_a');
        $this->assertEquals(array('key_a' => array(), 'key_b' => 'value'), $this->array);
    }

    public function testMergeNoOverwrite()
    {
        $expected = array(
            'key_a' => array(
                'subkey_a' => 'value_a'
            ),
            'key_b' => 'value',
            'key_c' => 'value_c'
        );
        $merge    = array(
                    'key_a' => 'anything',
                    'key_c' => 'value_c'
        );
        $actual   = ArrayUtils::merge($this->array, $merge, false);
        $this->assertEquals($expected, $actual);
    }

    public function testMergeOverwrite()
    {
        $expected = array(
            'key_a' => 'something',
            'key_b' => 'value',
            'key_c' => 'value_c'
        );
        $merge    = array(
            'key_a' => 'something',
            'key_c' => 'value_c'
        );
        $actual   = ArrayUtils::merge($this->array, $merge);
        $this->assertEquals($expected, $actual);
    }
}
