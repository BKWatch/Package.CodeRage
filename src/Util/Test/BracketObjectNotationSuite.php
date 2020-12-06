<?php

/**
 * Defines the class CodeRage\Util\Test\BracketObjectNotationSuite
 *
 * File:        CodeRage/Util/Test/BracketObjectNotationSuite.php
 * Date:        Tue May 15 00:18:28 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Test\Assert;
use CodeRage\Util\BracketObjectNotation as BON;


/**
 * Test suite for the class CodeRage\Util\BracketObjectNotation
 */
class BracketObjectNotationSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * Constructs an instance of CodeRage\Util\Test\BracketObjectNotationSuite
     */
    public function __construct()
    {
        parent::__construct(
            "BracketObjectNotation Test Suite",
            "Tests the class CodeRage\Util\BracketObjectNotation"
        );
    }

    /**
     * Tests constructing array of strings incrementally using assignments of
     * the form x[]=y
     */
    public function testDecodeStack()
    {
        $this->checkDecode(
            ['stack[]=one', 'stack[]=two', 'stack[]=three'],
            (object) ['stack' => ['one', 'two', 'three']]
        );
    }

    /**
     * Tests constructing a structure containing an array of strings
     * incrementally using assignments of the form x[y][]=x
     */
    public function testDecodeEmbeddedStack()
    {
        $this->checkDecode(
            ['foo[stack][]=one', 'foo[stack][]=two', 'foo[stack][]=three'],
            (object) ['foo' => (object) ['stack' => ['one', 'two', 'three']]]
        );
    }

    /**
     * Tests constructing array of strings by explicitly specifying array
     * offsets
     */
    public function testDecodeArrayOfStrings()
    {
        $this->checkDecode(
            ['array[0]=one', 'array[1]=two', 'array[2]=three'],
            (object) ['array' => ['one', 'two', 'three']]
        );
    }

    /**
     * Tests constructing array of string-valued objects
     */
    public function testDecodeArrayOfStructs()
    {
        $this->checkDecode(
            [
                'array[0][a1]=a2',
                'array[0][b1]=b2',
                'array[1][c1]=c2',
                'array[1][d1]=d2',
                'array[2][e1]=e2',
                'array[2][f1]=f2',
            ],
            (object) [
                'array' => [
                    (object) [
                        'a1' => 'a2',
                        'b1' => 'b2',
                    ],
                    (object) [
                        'c1' => 'c2',
                        'd1' => 'd2',
                    ],
                    (object) [
                        'e1' => 'e2',
                        'f1' => 'f2',
                    ]
                ]
            ]
        );
    }

    /**
     * Tests constructing array of arrays incrementally using assignments of
     * the form x[y][]=z
     */
    public function testDecodeArrayOfStacks()
    {
        $this->checkDecode(
            [
                'array[0][]=a1',
                'array[0][]=a2',
                'array[0][]=a3',
                'array[1][]=b1',
                'array[1][]=b2',
                'array[1][]=b3',
                'array[2][]=c1',
                'array[2][]=c2',
                'array[2][]=c3',
            ],
            (object) [
                'array' =>
                    [['a1', 'a2', 'a3'], ['b1', 'b2', 'b3'], ['c1', 'c2', 'c3']]
            ]
        );
    }

    /**
     * Tests constructing array of arrays explicitly specifying array offsets
     */
    public function testDecodeArrayOfArrays()
    {
        $this->checkDecode(
            [
                'array[0][0]=a1',
                'array[0][1]=a2',
                'array[0][2]=a3',
                'array[1][0]=b1',
                'array[1][1]=b2',
                'array[1][2]=b3',
                'array[2][0]=c1',
                'array[2][1]=c2',
                'array[2][2]=c3',
            ],
            (object) [
                'array' =>
                    [['a1', 'a2', 'a3'], ['b1', 'b2', 'b3'], ['c1', 'c2', 'c3']]
            ]
        );
    }

    /**
     * Tests constructing an object with string-valued properties
     */
    public function testDecodeStruct1()
    {
        $this->checkDecode(
            ['a1=a2', 'b1=b2', 'c1=c2'],
            (object) ['a1' => 'a2', 'b1' => 'b2', 'c1' => 'c2']
        );
    }

    /**
     * Tests constructing an object with array-valued properties
     */
    public function testDecodeStructOfArrays()
    {
        $this->checkDecode(
            [
                'one[]=a1',
                'one[]=a2',
                'one[]=a3',
                'two[]=b1',
                'two[]=b2',
                'two[]=b3',
                'three[]=c1',
                'three[]=c2',
                'three[]=c3',
            ],
            (object) [
                'one' => ['a1', 'a2', 'a3'],
                'two' => ['b1', 'b2', 'b3'],
                'three' => ['c1', 'c2', 'c3']
            ]
        );
    }

    /**
     * Tests constructing an object with string-valued properties
     */
    public function testDecodeStructOfStructs()
    {
        $this->checkDecode(
            [
                'one[a1]=a2',
                'one[a3]=a4',
                'two[b1]=b2',
                'two[b3]=b4',
                'three[c1]=c2',
                'three[c3]=c4'
            ],
            (object) [
                'one' => (object) ['a1' => 'a2', 'a3' => 'a4'],
                'two' => (object) ['b1' => 'b2', 'b3' => 'b4'],
                'three' => (object) ['c1' => 'c2', 'c3' => 'c4']
            ]
        );
    }

    /**
     * Tests constructing a top-level array of strings
     */
    public function testDecodeTopLevelArray()
    {
        $this->checkDecode(
            ['[0]=one', '[1]=two', '[2]=three'],
            ['one', 'two', 'three']
        );
    }

    /**
     * Tests constructing top-level array of string-valued objects
     */
    public function testDecodeTopLevelArrayOfStructs()
    {
        $this->checkDecode(
            [
                '[0][a1]=a2',
                '[0][b1]=b2',
                '[1][c1]=c2',
                '[1][d1]=d2',
                '[2][e1]=e2',
                '[2][f1]=f2',
            ],
            [
                (object) [
                    'a1' => 'a2',
                    'b1' => 'b2',
                ],
                (object) [
                    'c1' => 'c2',
                    'd1' => 'd2',
                ],
                (object) [
                    'e1' => 'e2',
                    'f1' => 'f2',
                ]
            ]
        );
    }

    /**
     * Tests constructing top-level array of arrays incrementally using
     * assignments of the form [y][]=z
     */
    public function testDecodeTopEvelArrayOfStacks()
    {
        $this->checkDecode(
            [
                '[0][]=a1',
                '[0][]=a2',
                '[0][]=a3',
                '[1][]=b1',
                '[1][]=b2',
                '[1][]=b3',
                '[2][]=c1',
                '[2][]=c2',
                '[2][]=c3',
            ],
            [['a1', 'a2', 'a3'], ['b1', 'b2', 'b3'], ['c1', 'c2', 'c3']]
        );
    }

    /**
     * Tests constructing top-level array of arrays explicitly specifying array
     * offsets
     */
    public function testDecodeTopLevelArrayOfArrays()
    {
        $this->checkDecode(
            [
                '[0][0]=a1',
                '[0][1]=a2',
                '[0][2]=a3',
                '[1][0]=b1',
                '[1][1]=b2',
                '[1][2]=b3',
                '[2][0]=c1',
                '[2][1]=c2',
                '[2][2]=c3',
            ],
            [['a1', 'a2', 'a3'], ['b1', 'b2', 'b3'], ['c1', 'c2', 'c3']]
        );
    }

    /**
     * Tests encoding and decoding a complex object
     */
    public function testRoundtrip1()
    {
        $object =
            (object) [
                'name' => 'Sam',
                'age' => '99.5',
                'veteran' => 'true',
                'children' =>
                    [
                        (object) [
                            'name' => 'Bob',
                            'age' => '55',
                            'veteran' => 'false',
                            'children' => [
                                (object) [
                                    'name' => 'Debby',
                                    'age' => '21',
                                    'veteran' => 'true'
                                ],
                                (object) [
                                    'name' => 'Karl',
                                    'age' => '23',
                                    'veteran' => 'false'
                                ]
                            ]
                        ],
                        (object) [
                            'name' => 'Wendy',
                            'age' => '30',
                            'veteran' => 'false'
                        ],
                        (object) [
                            'name' => 'Sarah',
                            'age' => '44',
                            'veteran' => 'false',
                            'accounts' =>
                                [
                                    (object) [
                                        'type' => 'checking',
                                        'number' => '2845791',
                                        'institution' =>
                                            "People's Bank of Brighton Beach"
                                    ]
                                ]
                        ]
                    ],
                'accounts' =>
                    [
                        (object) [
                            'type' => 'checking',
                            'number' => '2893742',
                            'institution' =>
                                "Nevada Famers Lending"
                        ],
                        (object) [
                            'type' => 'savings',
                            'number' => '1934783',
                            'institution' =>
                                "People's Bank of Brighton Beach"
                        ]
                    ]

            ];
        $this->checkRoundtrip($object);
    }

    private function checkEncode($object, $assignments)
    {
        Assert::equal(BON::encode($object), $assignments);
    }

    private function checkDecode($assignments, $object)
    {
        Assert::equal(BON::decode($assignments), $object);
    }

    private function checkRoundtrip($object)
    {
        Assert::equal(BON::decode(BON::encode($object)), $object);
    }
}
