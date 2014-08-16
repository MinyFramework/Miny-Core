<?php

namespace Miny\Utils;

class ArrayReferenceWrapperTest extends \PHPUnit_Framework_TestCase
{
    private $array;
    private $object;

    public function setUp()
    {
        $this->array  = ['foo', 'bar', new \stdClass(), [1, 2]];
        $this->object = new ArrayReferenceWrapper($this->array);
    }

    public function testValuesAreReturnedByReference()
    {
        $this->assertSame($this->array[2], $this->object[2]);
    }

    public function testValuesAreChangedInTheSourceArray()
    {
        unset($this->object[2]);
        $this->object[1]    = 'foobar';
        $this->object[3][2] = 3;
        $this->object[4]    = 'baz';

        $this->assertEquals('foobar', $this->array[1]);
        $this->assertEquals('baz', $this->array[4]);
        $this->assertEquals(3, $this->array[3][2]);
        $this->assertFalse(isset($this->array[2]));
    }

    public function testAddReservesReferenceProperty()
    {
        $this->object->add(['baz' => 'foobar']);

        $this->assertTrue(isset($this->array['baz']));
        $this->object['baz'] = 'value';
        $this->assertEquals('value', $this->array['baz']);
    }
}
