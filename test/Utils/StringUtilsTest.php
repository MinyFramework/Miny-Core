<?php

namespace Miny\Test\Utils;

use Miny\Utils\StringUtils;
use PHPUnit_Framework_TestCase;

class StringUtilsTest extends PHPUnit_Framework_TestCase
{
    public function testCompare()
    {
        $this->assertFalse(StringUtils::compare('string', 'ing'));
        $this->assertFalse(StringUtils::compare('length_1', 'length_2'));
        $this->assertTrue(StringUtils::compare('string', 'string'));
    }

    public function testCamelize()
    {
        $this->assertEquals('camelizedString', StringUtils::camelize('camelized string'));
        $this->assertEquals('camelizedString', StringUtils::camelize('camelized_string'));
        $this->assertEquals('camelizedString', StringUtils::camelize('camelized String'));
        $this->assertEquals('camelizedString', StringUtils::camelize('camelized_ String'));
    }

    public function testDecamelize()
    {
        $this->assertEquals('decamelized string', StringUtils::decamelize('decamelizedString'));
        $this->assertEquals(
            'decamelized_string',
            StringUtils::decamelize('decamelizedString', '_')
        );
    }
}
