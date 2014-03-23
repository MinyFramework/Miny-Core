<?php

namespace Miny\HTTP;

class HeadersTest extends \PHPUnit_Framework_TestCase
{
    public function sanitizeProvider()
    {
        return array(
            array('Content-Type', 'content-type'),
            array('Content_Type', 'content-type'),
        );
    }

    /**
     * @dataProvider sanitizeProvider
     */
    public function testSanitize($input, $expected)
    {
        $this->assertEquals($expected, Headers::sanitize($input));
    }

    public function testHasHeader()
    {
        $headers = new Headers;

        $this->assertFalse($headers->has('expect'));
        $headers->set('Expect', 'asd');
        $headers->set('accept', 'asd');
        $headers->set('accept', 'foo');
        $this->assertTrue($headers->has('expect'));
        $this->assertTrue($headers->has('expect', 'asd'));
        $this->assertFalse($headers->has('expect', 'foo'));
        $this->assertTrue($headers->has('accept', 'asd'));
        $this->assertTrue($headers->has('accept', 'foo'));
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage expect header is not set.
     */
    public function testGetNonexistentHeader()
    {
        $headers = new Headers;
        $headers->get('Expect');
    }

    public function testGetHeader()
    {
        $headers = new Headers;
        $headers->set('Expect', 'foo');
        $this->assertEquals('foo', $headers->get('Expect'));
    }

    public function testMultipleHeaders()
    {
        $headers = new Headers;

        $headers->set('Expect', 'asd');
        $headers->set('Expect', 'foo');

        $this->assertContains('asd', $headers->get('expect', false));
        $this->assertContains('foo', $headers->get('expect', false));
        $this->assertEquals('asd, foo', $headers->get('expect', true));
    }

    public function testOverrideHeaders()
    {
        $headers = new Headers;

        $headers->set('foo-header', 'asd');
        $headers->set('foo-header', 'foo');

        $this->assertEquals('foo', $headers->get('foo-header'));
    }

    public function testRawHeaders()
    {
        $headers = new Headers;

        $headers->set('Expect', 'something');

        $headers->setRaw('Raw foo');
        $headers->setRaw('Raw bar');

        $this->assertContains('Raw foo', $headers->getRawHeaders());
        $this->assertContains('Raw foo', $headers->getRawHeaders());
    }

    public function testReset()
    {
        $headers = new Headers;

        $headers->set('Expect', 'something');
        $headers->setRaw('Raw foo');
        $headers->reset();

        $this->assertFalse($headers->has('expect'));
        $this->assertEmpty($headers->getRawHeaders());
    }

    public function testToString()
    {
        $headers = new Headers;

        $headers->set('Expect', 'something');
        $headers->setRaw('Raw foo');

        $expected = "expect: something\nRaw foo\n";

        $this->assertEquals($expected, (string)$headers);
    }

    public function testSerialize()
    {
        $headers = new Headers;

        $headers->set('Expect', array('something', 'something else'));
        $headers->setRaw('Raw foo');

        $new_headers = unserialize(serialize($headers));

        $expected = "expect: something, something else\nRaw foo\n";

        $this->assertEquals($expected, (string)$new_headers);
    }

    public function testAddHeaders()
    {
        $headers = new Headers;
        $headers->set('Expect', 'something');

        $other = new Headers;
        $other->set('Expect', 'something else');
        $other->setRaw('Raw foo');

        $headers->addHeaders($other);

        $this->assertEquals('something, something else', $headers->get('expect'));
        $this->assertContains('Raw foo', $headers->getRawHeaders());
    }

    public function testRemoveHeaders()
    {
        $headers = new Headers;
        $headers->set('Expect', array('a', 'b', 'c'));
        $headers->set('foo', 'bar');

        $headers->remove('Expect', 'a');

        $this->assertContains('b', $headers->get('expect'));
        $this->assertContains('c', $headers->get('expect'));

        $headers->remove('Expect', 'c');

        $this->assertEquals('b', $headers->get('expect'));

        $headers->remove('expect');
        $this->assertFalse($headers->has('expect'));

        $headers->remove('foo', 'baz');
        $this->assertEquals('bar', $headers->get('foo'));

        $headers->remove('foo', 'bar');
        $this->assertFalse($headers->has('foo'));

        // should not do anything
        $headers->remove('nonexistent');
    }
}
