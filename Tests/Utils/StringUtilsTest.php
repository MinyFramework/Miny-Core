<?php

namespace Miny\Utils;

use PHPUnit_Framework_TestCase;

class StringUtilsTest extends PHPUnit_Framework_TestCase
{
    public function testCompare()
    {
        $this->assertFalse(StringUtils::compare('string', 'ing'));
        $this->assertFalse(StringUtils::compare('length_1', 'length_2'));
        $this->assertTrue(StringUtils::compare('string', 'string'));
    }

    public function testStartsWith()
    {
        $this->assertTrue(StringUtils::startsWith('string', 'str'));
        $this->assertFalse(StringUtils::startsWith('foo', 'bar'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(StringUtils::endsWith('string', 'ing'));
        $this->assertFalse(StringUtils::endsWith('foo', 'bar'));
    }
}

?>
