<?php

namespace Miny\Router;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $routerMock;

    public function setUp()
    {
        $this->routerMock = $this->getMockBuilder('\\Miny\\Router\\Router')
            ->disableOriginalConstructor()
            ->setMethods(array('add'))
            ->getMock();
    }

    public function testThatSingularResourceRoutesAreGenerated()
    {
        $resource = new Resource('resource');

        $this->routerMock
            ->expects($this->exactly(6))
            ->method('add');

        $resource->register($this->routerMock);
    }

    public function testThatPluralResourceRoutesAreGenerated()
    {
        $resource = new Resource('resource', 'resources');

        $this->routerMock
            ->expects($this->exactly(7))
            ->method('add');

        $resource->register($this->routerMock);
    }

    public function testThatResourceParentModifiesRoutes()
    {
        $resource = new Resource('resource', 'resources');
        $resource->setParent(new Resource('parent'));

        $this->routerMock
            ->expects($this->exactly(7))
            ->method('add');

        $resource->register($this->routerMock);
    }
}
