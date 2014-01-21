<?php

namespace Miny\Utils;

use Miny\Utils\Exceptions\AssertationException;
use PHPUnit_Framework_TestCase;

class TestClass
{
    private $elements;

    public function __construct()
    {
        $this->elements = func_get_args();
    }

    public function getElements()
    {
        return $this->elements;
    }
}

class UtilsTest extends PHPUnit_Framework_TestCase
{

    public function testAssert()
    {
        Utils::assert(true);
        try {
            Utils::assert(false, 'message');
            $this->fail('Assert should throw an AssertationException');
        } catch (AssertationException $e) {
            $this->assertEquals('message', $e->getMessage());
        }
    }

    public function instantiateProvider()
    {
        return array(
            array(array()),
            array(array(1)),
            array(array(1, 2)),
            array(array(1, 2, 3)),
            array(array(1, 2, 3, 4)),
            array(array(1, 2, 3, 4, 5)),
            array(array(1, 2, 3, 4, 5, 6)),
            array(array(1, 2, 3, 4, 5, 6, 7)),
        );
    }

    /**
     * @dataProvider instantiateProvider
     */
    public function testInstantiateWithArguments($args)
    {
        $class = Utils::instantiate('Miny\Utils\TestClass', $args);
        $this->assertEquals($class->getElements(), $args);
    }

    public function testInstantiateWithoutArguments()
    {
        $class = Utils::instantiate('Miny\Utils\TestClass');
        $this->assertEquals($class->getElements(), array());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Class not found: Foo\BarClass
     */
    public function testInstantiateNonExistingClass()
    {
        Utils::instantiate('Foo\BarClass');
    }
}

?>
