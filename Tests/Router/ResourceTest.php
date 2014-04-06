<?php

namespace Miny\Router;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $parserMock;

    public function setUp()
    {
        $this->parserMock = $this->getMockForAbstractClass('\\Miny\\Router\\AbstractRouteParser');

        $this->parserMock
            ->expects($this->any())
            ->method('parse')
            ->will($this->returnValue(new Route()));

        $this->router = new Router($this->parserMock);
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
        $this->assertSame($resource, $resource->register($this->router));
        $this->assertSame($resource, $resource->shallow());
    }

    public function testThatSingularResourceRoutesAreGenerated()
    {
        $resource = new Resource('resource');

        $this->parserMock
            ->expects($this->exactly(6))
            ->method('parse');

        $resource->register($this->router);

        $this->assertTrue($this->router->has('resource'));
        $this->assertTrue($this->router->has('new_resource'));
        $this->assertTrue($this->router->has('edit_resource'));
    }

    public function testThatSingularResourceShouldNotHaveMemberRoutes()
    {
        $resource = new Resource('resource');
        $resource
            ->member('foo', 0)
            ->member('bar', 0)
            ->member('baz', 0)
            ->member('foobar', 0);

        $this->parserMock
            ->expects($this->exactly(6))
            ->method('parse');

        $resource->register($this->router);
    }

    public function testThatPluralResourceRoutesAreGenerated()
    {
        $resource = new Resource('resource', 'resources');

        $this->parserMock
            ->expects($this->exactly(7))
            ->method('parse');

        $resource->register($this->router);

        $this->assertTrue($this->router->has('resource'));
        $this->assertTrue($this->router->has('resources'));
        // This assertion tests that named collection routes also have singular names.
        $this->assertTrue($this->router->has('new_resource'));
        $this->assertTrue($this->router->has('edit_resource'));
    }

    public function testThatResourceParentModifiesRoutes()
    {
        $resource = new Resource('resource', 'resources');
        $resource->setParent(new Resource('parent'));

        $this->parserMock
            ->expects($this->exactly(7))
            ->method('parse')
            ->with(
                $this->logicalOr(
                    $this->equalTo('parent/resources/collection'),
                    $this->equalTo('parent/resources/{id:\d+}/member'),
                    $this->equalTo('parent/resources/{id:\d+}'),
                    $this->equalTo('parent/resources/{id:\d+}/edit'),
                    $this->equalTo('parent/resources/new'),
                    $this->equalTo('parent/resources')
                )
            );

        $resource->register($this->router);
    }

    public function testThatResourcePluralParentModifiesRoutes()
    {
        $resource = new Resource('resource', 'resources');
        $resource->setParent(new Resource('parent', 'parents'));

        $this->parserMock
            ->expects($this->exactly(7))
            ->method('parse')
            ->with(
                $this->logicalOr(
                    $this->equalTo('parents/{parent_id:\d+}/resources/collection'),
                    $this->equalTo('parents/{parent_id:\d+}/resources/{id:\d+}/member'),
                    $this->equalTo('parents/{parent_id:\d+}/resources/{id:\d+}'),
                    $this->equalTo('parents/{parent_id:\d+}/resources/{id:\d+}/edit'),
                    $this->equalTo('parents/{parent_id:\d+}/resources/new'),
                    $this->equalTo('parents/{parent_id:\d+}/resources')
                )
            );

        $resource->register($this->router);
    }

    public function testThatResourceParentCanHaveDifferentIdPattern()
    {
        $parent = new Resource('parent', 'parents');
        $parent->idPattern('[^/]+');

        $resource = new Resource('resource', 'resources');
        $resource->setParent($parent);

        $this->parserMock
            ->expects($this->exactly(7))
            ->method('parse')
            ->with(
                $this->logicalOr(
                    $this->equalTo('parents/{parent_id:[^/]+}/resources/collection'),
                    $this->equalTo('parents/{parent_id:[^/]+}/resources/{id:\d+}/member'),
                    $this->equalTo('parents/{parent_id:[^/]+}/resources/{id:\d+}'),
                    $this->equalTo('parents/{parent_id:[^/]+}/resources/{id:\d+}/edit'),
                    $this->equalTo('parents/{parent_id:[^/]+}/resources/new'),
                    $this->equalTo('parents/{parent_id:[^/]+}/resources')
                )
            );

        $resource->register($this->router);

        $routes = $this->router->getAll();

        $this->assertArrayHasKey('parent_resources', $routes);
        $this->assertArrayHasKey('parent_resource', $routes);
        $this->assertArrayHasKey('new_parent_resource', $routes);
        $this->assertArrayHasKey('edit_parent_resource', $routes);
    }

    public function testThatOnlyReturnsRoutesForOnlySelectMethods()
    {
        $resource = new Resource('resource', 'resources');
        $resource->only('index', 'new', 'edit');

        $this->parserMock
            ->expects($this->exactly(3))
            ->method('parse')
            ->with(
                $this->logicalOr(
                    $this->equalTo('resources/{id:\d+}/edit'),
                    $this->equalTo('resources'),
                    $this->equalTo('resources/new')
                )
            );

        $resource->register($this->router);

        $routes = $this->router->getAll();

        $this->assertCount(3, $routes);
        $this->assertArrayHasKey('resources', $routes);
        $this->assertArrayHasKey('new_resource', $routes);
        $this->assertArrayHasKey('edit_resource', $routes);
    }

    public function testThatNewMethodsCanBeAdded()
    {
        $resource = new Resource('resource', 'resources');

        $resource->member('member', Route::METHOD_GET);
        $resource->collection('collection', Route::METHOD_GET);

        $this->parserMock
            ->expects($this->exactly(9))
            ->method('parse')
            ->with(
                $this->logicalOr(
                    $this->equalTo('resources/collection'),
                    $this->equalTo('resources/{id:\d+}/member'),
                    $this->equalTo('resources/{id:\d+}'),
                    $this->equalTo('resources/{id:\d+}/edit'),
                    $this->equalTo('resources/new'),
                    $this->equalTo('resources')
                )
            );

        $resource->register($this->router);

        $routes = $this->router->getAll();
        $this->assertArrayHasKey('member_resource', $routes);
        $this->assertArrayHasKey('collection_resource', $routes);
    }

    public function testThatExceptExcludesRoutes()
    {
        $resource = new Resource('resource', 'resources');

        $resource->except('new', 'edit');

        $this->parserMock
            ->expects($this->exactly(5))
            ->method('parse')
            ->with(
                $this->logicalNot(
                    $this->logicalOr(
                        $this->equalTo('resources/{id:\d+}/edit'),
                        $this->equalTo('resources/new')
                    )
                )
            );

        $resource->register($this->router);
        $routes = $this->router->getAll();

        $this->assertCount(5, $routes);
        $this->assertArrayNotHasKey('new_resource', $routes);
        $this->assertArrayNotHasKey('edit_resource', $routes);
    }

    public function testResourceNameAndMethodShouldBeSetToControllerAndAction()
    {
        $resource = new Resource('resource', 'resources');
        $resource->only('index');
        $resource->register($this->router);

        $array = $this->router->get('resource')->getDefaultValues();
        $this->assertEquals('Resources', $array['controller']);
        $this->assertEquals('index', $array['action']);
    }

    public function testThatParametersAreSet()
    {
        $resource = new Resource('resource', 'resources');
        $resource->only('index');
        $resource->set(
            array(
                'controller' => 'Foo',
                'foo'        => 'bar'
            )
        );
        $resource->register($this->router);

        $array = $this->router->get('resource')->getDefaultValues();
        $this->assertEquals('Foo', $array['controller']);
        $this->assertEquals('bar', $array['foo']);
    }

    public function testMemberMethodsOfShallowNestedResourcesShouldNotBePrefixedWithParent()
    {
        $resource = new Resource('resource', 'resources');
        $resource->setParent(new Resource('parent', 'parents'));
        $resource->shallow();

        $this->parserMock
            ->expects($this->exactly(7))
            ->method('parse')
            ->with(
                $this->logicalOr(
                    $this->equalTo('parents/{parent_id:\d+}/resources'),
                    $this->equalTo('parents/{parent_id:\d+}/resources/new'),
                    $this->equalTo('resources/{id:\d+}'),
                    $this->equalTo('resources/{id:\d+}/edit')
                )
            );

        $resource->register($this->router);
        $routes = $this->router->getAll();

        $this->assertArrayHasKey('parent_resources', $routes);
        $this->assertArrayHasKey('new_parent_resource', $routes);
        $this->assertArrayHasKey('resource', $routes);
        $this->assertArrayHasKey('edit_resource', $routes);
    }

    public function testResourceMethodShouldSetTheCorrectParentChildRelationship()
    {
        $child = new Resource('child', 'children');
        $resource = new Resource('resource', 'resources');
        $resource->resource($child);

        $child->register($this->router);
        $routes = $this->router->getAll();

        $this->assertArrayHasKey('resource_children', $routes);
    }
}
