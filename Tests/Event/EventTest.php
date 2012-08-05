<?php

namespace Miny\Event;

require_once dirname(__FILE__) . '/../../Event/Event.php';

/**
 * Test class for Event.
 * Generated by PHPUnit on 2012-08-02 at 23:44:27.
 */
class EventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Event
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Event('test_event', array(
                    'param1_name' => 'param1_value',
                    'param2_name' => 'param2_value'
                ));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /**
     * @covers Miny\Event\Event::isHandled
     * @covers Miny\Event\Event::setHandled
     */
    public function testHandled()
    {
        $this->assertFalse($this->object->isHandled());
        $this->object->setHandled();
        $this->assertTrue($this->object->isHandled());
    }

    /**
     * @covers Miny\Event\Event::getName
     */
    public function testGetName()
    {
        $this->assertEquals($this->object->getName(), 'test_event');
    }

    /**
     * @covers Miny\Event\Event::__toString
     */
    public function test__toString()
    {
        $this->assertEquals((string) $this->object, 'test_event');
    }

    /**
     * @covers Miny\Event\Event::hasParameter
     */
    public function testHasParameter()
    {
        $this->assertTrue($this->object->hasParameter('param1_name'));
        $this->assertFalse($this->object->hasParameter('nonexistent_param3'));
    }

    /**
     * @covers Miny\Event\Event::getParameter
     */
    public function testGetParameter()
    {
        $this->assertEquals($this->object->getParameter('param1_name'), 'param1_value');
        try {
            $this->object->getParameter('nonexistent_param');
            $this->fail('An expected exception has not been raised.');
        } catch (\OutOfBoundsException $e) {

        }
    }

    /**
     * @covers Miny\Event\Event::getParameters
     */
    public function testGetParameters()
    {
        $parameters = array(
            'param1_name' => 'param1_value',
            'param2_name' => 'param2_value'
        );
        $this->assertEquals($this->object->getParameters(), $parameters);
    }

    /**
     * @covers Miny\Event\Event::setResponse
     * @todo Implement testSetResponse().
     * @todo Implement testHasResponse().
     * @todo Implement testGetResponse().
     */
    public function testResponse()
    {
        $this->assertFalse($this->object->hasResponse());
        $this->object->setResponse('foo');
        $this->assertTrue($this->object->hasResponse());
    }

}

?>