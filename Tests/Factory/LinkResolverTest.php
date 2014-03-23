<?php

namespace Miny\Factory;

class LinkResolverTest extends \PHPUnit_Framework_TestCase
{
    private $parameterContainerMock;
    private $resolver;

    protected function setUp()
    {
        $this->parameterContainerMock = $this->getMock(
            '\Miny\Factory\ParameterContainer',
            array('offsetGet', 'resolveLinks', 'resolveLinksInString')
        );

        $this->resolver = new LinkResolver($this->parameterContainerMock);
    }

    public function testResolverShouldNotTouchNonStringArguments()
    {
        $this->parameterContainerMock
            ->expects($this->never())
            ->method('offsetGet');

        $this->parameterContainerMock
            ->expects($this->never())
            ->method('resolveLinks');

        $this->assertEquals(5, $this->resolver->resolveReferences(5));
        $this->assertEquals('f', $this->resolver->resolveReferences('f'));
    }

    public function testLinksStartingWithAtShouldBeResolvedByOffsetGet()
    {
        $this->parameterContainerMock
            ->expects($this->once())
            ->method('offsetGet');

        $this->parameterContainerMock
            ->expects($this->never())
            ->method('resolveLinks');

        $this->resolver->resolveReferences('@foo');
    }

    public function testLinksInCurlyBracesShouldBeResolvedByResolveLinksInString()
    {
        $this->parameterContainerMock
            ->expects($this->once())
            ->method('resolveLinksInString')
            ->will($this->returnValue('foo'));

        $this->parameterContainerMock
            ->expects($this->never())
            ->method('offsetGet');

        $this->assertEquals('foo', $this->resolver->resolveReferences('{@foo}'));
        $this->assertEquals('@foo', $this->resolver->resolveReferences('\@foo'));
    }

    public function testThatLinksInArraysAreResolved()
    {
        $this->parameterContainerMock
            ->expects($this->exactly(2))
            ->method('resolveLinksInString')
            ->will($this->onConsecutiveCalls('foo', 'bar'));

        $this->assertEquals(
            array('foo', 'bar'),
            $this->resolver->resolveReferences(array('{@foo}', '{@bar}'))
        );
    }

}
