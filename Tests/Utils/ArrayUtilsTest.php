<?php

namespace Miny\Utils;

use OutOfBoundsException;
use PHPUnit_Framework_TestCase;

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

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Path must be an array or a string.
     */
    public function testExistsByPathException()
    {
        $this->assertTrue(ArrayUtils::existsByPath($this->array, 5));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage ArrayUtils::existsByPath expects an array or an ArrayAccess object.
     */
    public function testExistsByPathArrayException()
    {
        $this->assertTrue(ArrayUtils::existsByPath('something', 'path:to:something'));
    }

    public function testGetByPath()
    {
        $this->assertEquals('value_a', ArrayUtils::getByPath($this->array, 'key_a:subkey_a'));
        $this->assertEquals('value_a', ArrayUtils::getByPath($this->array, array('key_a', 'subkey_a')));
        $this->assertEquals(null, ArrayUtils::getByPath($this->array, array('key_a', 'subkey_b')));
        $this->assertEquals('default', ArrayUtils::getByPath($this->array, array('key_a', 'subkey_b'), 'default'));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage ArrayUtils::getByPath expects an array or an ArrayAccess object.
     */
    public function testGetByPathArrayException()
    {
        $this->assertTrue(ArrayUtils::getByPath('something', 'path:to:something'));
    }

    public function testFindByPath()
    {
        $this->assertEquals('value_a', ArrayUtils::findByPath($this->array, 'key_a:subkey_a'));
        $this->assertEquals('value_a', ArrayUtils::findByPath($this->array, array('key_a', 'subkey_a')));
        $this->assertEquals(array(), ArrayUtils::findByPath($this->array, array('foo_path'), true));
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage Array key not found: key_a:subkey_b
     */
    public function testFindByPathNonexistent()
    {
        ArrayUtils::findByPath($this->array, array('key_a', 'subkey_b'));
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
        // Should not throw an exception.
        ArrayUtils::unsetByPath($this->array, 'key_a:subkey_a:foo');
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
