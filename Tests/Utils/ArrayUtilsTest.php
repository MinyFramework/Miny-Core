<?php

namespace Miny\Utils;

use InvalidArgumentException;
use OutOfBoundsException;

class ArrayUtilsTest extends \PHPUnit_Framework_TestCase
{
    private $array;

    protected function setUp()
    {
        $this->array = array(
            'key_a' => new \ArrayObject(array(
                    'subkey_a' => 'value_a'
                )),
            'key_b' => 'value',
            6       => 'six'
        );
    }

    public function testExistsByPath()
    {
        $this->assertFalse(ArrayUtils::existsByPath($this->array, 5));
        $this->assertTrue(ArrayUtils::existsByPath($this->array, 6));

        $this->assertTrue(ArrayUtils::existsByPath($this->array, 'key_a:subkey_a'));
        $this->assertTrue(ArrayUtils::existsByPath($this->array, array('key_a', 'subkey_a')));
        $this->assertFalse(ArrayUtils::existsByPath($this->array, array('key_b:subkey_a')));
        $this->assertFalse(ArrayUtils::existsByPath($this->array, array('key_b', 'subkey_a')));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExistsByPathArrayException()
    {
        ArrayUtils::existsByPath('something', 'path:to:something');
    }

    public function testGetByPath()
    {
        $this->assertEquals('six', ArrayUtils::getByPath($this->array, 6));
        $this->assertEquals('value', ArrayUtils::getByPath($this->array, 'key_b'));
        $this->assertEquals('value_a', ArrayUtils::getByPath($this->array, 'key_a:subkey_a'));
        $this->assertEquals('value_a', ArrayUtils::getByPath($this->array, array('key_a', 'subkey_a')));
        $this->assertEquals(null, ArrayUtils::getByPath($this->array, array('key_a', 'subkey_b')));
        $this->assertEquals('default', ArrayUtils::getByPath($this->array, array('key_a', 'subkey_b'), 'default'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetByPathArrayException()
    {
        ArrayUtils::getByPath('something', 'path:to:something');
    }

    public function testFindByPath()
    {
        $this->assertEquals('value_a', ArrayUtils::findByPath($this->array, 'key_a:subkey_a'));
        $this->assertEquals('value_a', ArrayUtils::findByPath($this->array, array('key_a', 'subkey_a')));
        $this->assertEquals(array(), ArrayUtils::findByPath($this->array, array('foo_path'), true));
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testFindByPathNonexistent()
    {
        ArrayUtils::findByPath($this->array, array('key_a', 'subkey_b'));
    }

    public function testSetByPath()
    {
        ArrayUtils::setByPath($this->array, 'key_a:subkey_a', 'new_value');
        ArrayUtils::setByPath($this->array, 'key_a:nonexistent:sub', 'value');
        ArrayUtils::setByPath($this->array, array('key', 'another', null), 'value');
        $this->assertEquals('new_value', ArrayUtils::getByPath($this->array, 'key_a:subkey_a'));
        $this->assertEquals('value', ArrayUtils::getByPath($this->array, 'key_a:nonexistent:sub'));
        $this->assertEquals('value', ArrayUtils::getByPath($this->array, 'key:another:0'));
    }

    public function testUnsetByPath()
    {
        ArrayUtils::unsetByPath($this->array, 'key_a:subkey_a');
        $this->assertFalse(isset($this->array['key_a']['subkey_a']));
        // Should not throw an exception.
        ArrayUtils::unsetByPath($this->array, 'key_a:subkey_a:foo');
    }

    public function testMergeNoOverwrite()
    {
        $expected = array(
            'key_a' => new \ArrayObject(array(
                    'subkey_a' => 'value_a'
                )),
            'key_b' => 'value',
            'key_c' => 'value_c',
            6       => 'six'
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
            'key_c' => 'value_c',
            6       => 'six'
        );
        $merge    = array(
            'key_a' => 'something',
            'key_c' => 'value_c'
        );
        $actual   = ArrayUtils::merge($this->array, $merge);
        $this->assertEquals($expected, $actual);
    }

    public function testImplodeIfArray()
    {
        $this->assertEquals(540, ArrayUtils::implodeIfArray(540, 'anything'));
        $this->assertEquals('1 glue 2', ArrayUtils::implodeIfArray(array(1, 2), ' glue '));
    }
}
