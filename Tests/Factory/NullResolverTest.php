<?php

namespace Miny\Factory;

class NullResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolver()
    {
        $resolver = new NullResolver();

        $this->assertEquals('something', $resolver->resolveReferences('something'));
        $this->assertEquals('foo', $resolver->resolveReferences('foo'));
    }
}
