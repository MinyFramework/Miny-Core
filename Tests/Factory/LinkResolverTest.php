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
            array('offsetGet', 'resolveLinks')
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

    public function testLinksInCurlyBracesShouldBeResolvedByResolveLinks()
    {
        $this->parameterContainerMock
            ->expects($this->never())
            ->method('offsetGet');

        $this->parameterContainerMock
            ->expects($this->once())
            ->method('resolveLinks');

        $this->resolver->resolveReferences('{@foo}');
    }

}
