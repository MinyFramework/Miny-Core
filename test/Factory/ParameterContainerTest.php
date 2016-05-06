<?php

namespace Miny\Test\Factory;

use Miny\Factory\ParameterContainer;

class ParameterContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ParameterContainer
     */
    protected $container;
    protected $parameters = [
        'array_a'      => [
            'param_b' => '{@value_b}',
            'array_b' => [
                'deep_parameter' => 'deep_value'
            ],
        ],
        'param_c'      => '{@array_a:param_b}',
        'value_b'      => 'value_c',
        'invalid_link' => '{@not_exists}',
        'some_item'    => 'some_value',
        'array'        => [
            'array' => [
                'array' => 'value'
            ]
        ]
    ];

    protected function setUp()
    {
        $this->container = new ParameterContainer($this->parameters);
    }

    public function testIssetParameters()
    {
        $this->assertTrue(isset($this->container['array_a']['param_b']));
        $this->assertTrue(isset($this->container['array_a:param_b']));
        $this->assertFalse(isset($this->container['array_a:param_not_exists']));
    }

    /**
     * Note: this also tests whether offsetGet accepts arrays.
     *
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Array key not found: no:path:that:exists
     */
    public function testOffsetGetException()
    {
        $this->container[['no', 'path', 'that', 'exists']];
    }

    public function testGetParameters()
    {
        $array_a_with_resolved_links = [
            'param_b' => 'value_c',
            'array_b' => [
                'deep_parameter' => 'deep_value'
            ]
        ];

        //simply get the item
        $this->assertEquals('value_c', $this->container['value_b']);

        //get the item from a deeper level
        $this->assertEquals('value_c', $this->container['array_a:param_b']);

        //should resolve links in array key and value, recursively
        $this->assertEquals($array_a_with_resolved_links, $this->container['array_a']);
        $this->assertEquals(
            $array_a_with_resolved_links['array_b'],
            $this->container['array_a:array_b']
        );

        //resolves a link that points to a link in a deeper level
        $this->assertEquals('value_c', $this->container['param_c']);

        //leave value untouched if it can't be resolved
        $this->assertEquals('{@not_exists}', $this->container['invalid_link']);
    }

    public function testSetParameters()
    {
        //overwrite
        $this->container['value_b'] = 'value_b';
        $this->assertEquals('value_b', $this->container['value_b']);

        //links should also change
        $this->assertEquals('value_b', $this->container['param_c']);

        //new item
        $this->assertFalse(isset($this->container['param_d']));
        $this->container['param_d'] = 'value_d';
        $this->assertTrue(isset($this->container['param_d']));
        $this->assertEquals('value_d', $this->container['param_d']);

        $this->container['some_array_param:subindex:another'] = 'value';
        $this->assertTrue(
            isset($this->container['some_array_param'], $this->container['some_array_param']['subindex'])
        );
        $this->assertTrue(isset($this->container['some_array_param']['subindex']['another']));
        $this->assertEquals('value', $this->container['some_array_param']['subindex']['another']);
    }

    public function testUnsetParameters()
    {
        //simple unset
        $this->assertTrue(isset($this->container['some_item']));

        unset($this->container['some_item']);
        $this->assertFalse(isset($this->container['some_item']));

        unset($this->container['array_a:array_b:deep_parameter']);
        $this->assertEquals([], $this->container['array_a:array_b']);

        //deleting a link
        unset($this->container['value_b']);
        $this->assertEquals('{@value_b}', $this->container['array_a:param_b']);

        //tricky case - don't delete keys with same name, only the one with the correct path
        $tricky_result = ['array' => []];

        unset($this->container['array:array:array']);
        $this->assertTrue(isset($this->container['array']));
        $this->assertTrue(isset($this->container['array:array']));
        $this->assertFalse(isset($this->container['array:array:array']));
        $this->assertEquals($tricky_result, $this->container['array']);
    }

    public function testParameterMerge()
    {
        $new_parameters  = [
            'array_a'      => [
                'param_b'           => 'some_value', //overwrite
                'additional_param'  => 'other_value', //new key
                'something'         => 'prefix_{@value_b}',
                'something_invalid' => 'prefix_{@invalid_link}'
            ],
            'not_a_string' => 5
        ];
        $expected_result = [
            'array_a'      => [
                'param_b'           => 'some_value',
                'array_b'           => [
                    'deep_parameter' => 'deep_value'
                ],
                'additional_param'  => 'other_value',
                'something'         => 'prefix_{@value_b}',
                'something_invalid' => 'prefix_{@invalid_link}'
            ],
            'param_c'      => '{@array_a:param_b}',
            'value_b'      => 'value_c',
            'invalid_link' => '{@not_exists}',
            'some_item'    => 'some_value',
            'array'        => [
                'array' => [
                    'array' => 'value'
                ]
            ],
            'not_a_string' => 5
        ];
        $this->container->addParameters($new_parameters);
        $this->assertEquals($expected_result, $this->container->toArray());
    }

    public function testParameterMergeWithoutOverwrite()
    {
        $new_parameters  = [
            'array_a' => [
                'param_b'          => 'some_value', //overwrite
                'additional_param' => 'other_value', //new key
            ]
        ];
        $expected_result = [
            'array_a'      => [
                'param_b'          => '{@value_b}',
                'array_b'          => [
                    'deep_parameter' => 'deep_value'
                ],
                'additional_param' => 'other_value'
            ],
            'param_c'      => '{@array_a:param_b}',
            'value_b'      => 'value_c',
            'invalid_link' => '{@not_exists}',
            'some_item'    => 'some_value',
            'array'        => [
                'array' => [
                    'array' => 'value'
                ]
            ]
        ];
        $this->container->addParameters($new_parameters, false);
        $this->assertEquals($expected_result, $this->container->toArray());
    }

    public function testThatSubTreeIsReturned()
    {
        $this->assertInstanceOf(
            '\\Miny\\Factory\\AbstractConfigurationTree',
            $this->container->getSubTree('array_a')
        );
    }
}
