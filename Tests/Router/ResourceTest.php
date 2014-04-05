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

    public function testFluentInterface()
    {
        $resource = new Resource('resource');

        $this->assertSame($resource, $resource->idPattern('\d+'));
        $this->assertSame($resource, $resource->setParent(new Resource('parent')));
        $this->assertSame($resource, $resource->except('index'));
        $this->assertSame($resource, $resource->only('edit'));
        $this->assertSame($resource, $resource->member('foo', Route::METHOD_GET));
        $this->assertSame($resource, $resource->collection('bar', Route::METHOD_GET));
        $this->assertSame($resource, $resource->register($this->routerMock));
    }

    public function testThatSingularResourceRoutesAreGenerated()
    {
        $resource = new Resource('resource');

        $this->routerMock
            ->expects($this->exactly(6))
            ->method('add');

        $resource->register($this->routerMock);
    }

    public function testThatSingularResourceShouldNotHaveMemberRoutes()
    {
        $resource = new Resource('resource');
        $resource
            ->member('foo', 0)
            ->member('bar', 0)
            ->member('baz', 0)
            ->member('foobar', 0);

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
            ->method('add')
            ->with($this->stringStartsWith('parent/resource'));

        $resource->register($this->routerMock);
    }

    public function testThatResourcePluralParentModifiesRoutes()
    {
        $resource = new Resource('resource', 'resources');
        $resource->setParent(new Resource('parent', 'parents'));

        $this->routerMock
            ->expects($this->exactly(7))
            ->method('add')
            ->with($this->stringStartsWith('parent/{parent_id:\d+}'))
            ->will($this->returnValue(new Route));

        $resource->register($this->routerMock);
    }

    public function testThatResourceParentCanHaveDifferentIdPattern()
    {
        $parent = new Resource('parent', 'parents');
        $parent->idPattern('[^/]+');

        $resource = new Resource('resource', 'resources');
        $resource->setParent($parent);

        $this->routerMock
            ->expects($this->exactly(7))
            ->method('add')
            ->with($this->stringStartsWith('parent/{parent_id:[^/]+}'))
            ->will($this->returnValue(new Route));

        $resource->register($this->routerMock);
    }

    public function testThatOnlyReturnsRoutesForOnlySelectMethods()
    {
        $resource = new Resource('resource', 'resources');
        $resource->only('index', 'new', 'edit');

        $this->routerMock
            ->expects($this->exactly(3))
            ->method('add');

        $this->routerMock
            ->expects($this->at(0))
            ->method('add')
            ->with($this->equalTo('resource/{id:\d+}/edit'));

        $this->routerMock
            ->expects($this->at(1))
            ->method('add')
            ->with($this->equalTo('resources'));

        $this->routerMock
            ->expects($this->at(2))
            ->method('add')
            ->with($this->equalTo('resources/new'));

        $resource->register($this->routerMock);
    }

    public function testThatNewMethodsCanBeAdded()
    {
        $resource = new Resource('resource', 'resources');

        $resource->member('member', Route::METHOD_GET);
        $resource->collection('collection', Route::METHOD_GET);

        $this->routerMock
            ->expects($this->exactly(9))
            ->method('add')
            ->with(
                $this->logicalOr(
                    $this->equalTo('resources/collection'),
                    $this->equalTo('resource/{id:\d+}/member'),
                    $this->equalTo('resource/{id:\d+}'),
                    $this->equalTo('resource/{id:\d+}/edit'),
                    $this->equalTo('resources/new'),
                    $this->equalTo('resources')
                )
            );

        $resource->register($this->routerMock);
    }

    public function testThatExceptExcludesRoutes()
    {
        $resource = new Resource('resource', 'resources');

        $resource->except('new', 'edit');

        $this->routerMock
            ->expects($this->exactly(5))
            ->method('add')
            ->with(
                $this->logicalNot(
                    $this->logicalOr(
                        $this->equalTo('resource/{id:\d+}/edit'),
                        $this->equalTo('resources/new')
                    )
                )
            );

        $resource->register($this->routerMock);
    }
}
