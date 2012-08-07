<?php

namespace Miny\Event;

require_once dirname(__FILE__) . '/../../Event/Event.php';

class EventTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new Event('test_event', array(
                    'param1_name' => 'param1_value',
                    'param2_name' => 'param2_value'
                ));
    }

    public function testHandled()
    {
        $this->assertFalse($this->object->isHandled());
        $this->object->setHandled();
        $this->assertTrue($this->object->isHandled());
    }

    public function testGetName()
    {
        $this->assertEquals('test_event', $this->object->getName());
    }

    public function test__toString()
    {
        $this->assertEquals('test_event', (string) $this->object);
    }

    public function testHasParameter()
    {
        $this->assertTrue($this->object->hasParameter('param1_name'));
        $this->assertFalse($this->object->hasParameter('nonexistent_param3'));
    }

    public function testGetParameter()
    {
        $this->assertEquals('param1_value', $this->object->getParameter('param1_name'));
        try {
            $this->object->getParameter('nonexistent_param');
            $this->fail('An expected exception has not been raised.');
        } catch (\OutOfBoundsException $e) {

        }
    }

    public function testGetParameters()
    {
        $parameters = array(
            'param1_name' => 'param1_value',
            'param2_name' => 'param2_value'
        );
        $this->assertEquals($parameters, $this->object->getParameters());
    }

    public function testResponse()
    {
        //no response set
        $this->assertFalse($this->object->hasResponse());
        $this->assertNull($this->object->getResponse());
        //response set
        $response = 'foo';
        $this->object->setResponse($response);
        $this->assertTrue($this->object->hasResponse());
        $this->assertEquals($response, $this->object->getResponse());
    }

}

?>
