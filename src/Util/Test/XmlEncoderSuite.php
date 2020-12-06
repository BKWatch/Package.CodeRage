<?php

/**
 * Defines the class CodeRage\Util\Test\XmlEncoderSuite
 * 
 * File:        CodeRage/Util/Test/XmlEncoderSuite.php
 * Date:        Mon May 21 13:34:26 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Test\Assert;
use CodeRage\Util\XmlEncoder;
use CodeRage\Xml;

/**
 * @ignore
 */

/**
 * Test suite for the class CodeRage\Util\XmlEncoder
 *
 */
class XmlEncoderSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * Constructs an instance of CodeRage\Util\Test\XmlEncoderSuite.
     */
    public function __construct()
    {
        parent::__construct(
            "XML Encoder Test Suite",
            "Tests the class CodeRage\Util\XmlEncoder"
        );
    }

    public function testScalarEncodingWithoutNamespace()
    {
        $encoder = self::noNamespaceEncoder();
        $actual =
            $encoder->encode('value', "hello")
                    ->ownerDocument
                    ->saveXml();
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <value>hello</value>';
        Assert::equivalentXmlData(
            $actual,
            $expected
        );
    }

    public function testScalarEncodingWithNamespace()
    {
        $encoder = self::namespaceEncoder();
        $actual =
            $encoder->encode('value', "hello")
                    ->ownerDocument
                    ->saveXml();
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <value xmlns="http://www.example.com">hello</value>';
        Assert::equivalentXmlData(
            $actual,
            $expected
        );
    }

    public function testEncodingWithoutNamespace()
    {
        // Test roundtrip encoding to avoid problems with mixed content nodes
        $encoder = self::noNamespaceEncoder();
        $actual =
            $encoder->decode(
                $encoder->encode('resident', self::complexDataStructure()),
                true
            );
        $expected =
            $encoder->decode(
                Xml::loadDocumentXml(self::complexDocument())->documentElement,
                true
            );
        Assert::equivalentData(
            $actual,
            $expected
        );
    }

    public function testEncodingWithNamespace()
    {
        // Test roundtrip encoding to avoid problems with mixed content nodes
        $encoder = self::namespaceEncoder();
        $actual =
            $encoder->decode(
                $encoder->encode('resident', self::complexDataStructure()),
                true
            );
        $expected =
            $encoder->decode(
                Xml::loadDocumentXml(self::complexDocumentWithNamespace())
                    ->documentElement,
                true
            );
        Assert::equivalentData(
            $actual,
            $expected
        );
    }

    public function testNilEncoding()
    {
        $encoder = self::xsiNilEncoder();
        $value = self::complexDataStructureWithNulls();
        Assert::equivalentData(
            $encoder->decode($encoder->encode('resident', $value)),
            $value
        );
    }

    public function testTypeEncoding()
    {
        $encoder = self::xsiTypeEncoder();
        $value = self::complexDataStructureWithTypedScalars();
        Assert::equivalentData(
            $encoder->decode($encoder->encode('resident', $value)),
            $value
        );
    }

    public function testEncodingFailure1()
    {
        $this->setExpectedException();
        $encoder = self::namespaceEncoder();
        $encoder->encode('geese', ['Sarah', 'Rebecca', 'Rachel']);
    }

    public function testEncodingFailure2()
    {
        $this->setExpectedException();
        $encoder = self::namespaceEncoder();
        $encoder->encode('value', new XmlEncoderSuiteClass);
    }

    public function testEncodingFailure3()
    {
        $this->setExpectedException();
        $encoder = self::namespaceEncoder();
        $encoder->encode('value', fopen('php://input', 'r'));
    }

    public function testScalarDecodingWithoutNamespace()
    {
        $encoder = self::noNamespaceEncoder();
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <value>hello</value>';
        $actual = $encoder->decode(Xml::loadDocumentXml($xml)->documentElement);
        $expected = 'hello';
        Assert::equivalentData($actual, $expected);
    }

    public function testScalarDecodingWithNamespace()
    {
        $encoder = self::namespaceEncoder();
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <value xmlns="http://www.example.com">hello</value>';
        $actual = $encoder->decode(Xml::loadDocumentXml($xml)->documentElement);
        $expected = 'hello';
        Assert::equivalentData($actual, $expected);
    }

    public function testDecodingFailure1()
    {
        $this->setExpectedException();
        $encoder = self::noNamespaceEncoder();
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <value xmlns="http://www.example.com">hello</value>';
        $encoder->decode(Xml::loadDocumentXml($xml)->documentElement, true);
    }

    public function testDecodingFailure2()
    {
        $this->setExpectedException();
        $encoder = self::namespaceEncoder();
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <value>hello</value>';
        $encoder->decode(Xml::loadDocumentXml($xml)->documentElement, true);
    }

    public function testSoapEncoding()
    {
        $encoder = self::noNamespaceEncoder();
        $actual = $encoder->fixSoapEncoding(self::complexSoapDataStructure());
        Assert::equivalentData(
            $actual,
            self::complexDataStructure()
        );
    }

    public function testSoapEncodingFailure1()
    {
        $this->setExpectedException();
        $encoder = self::noNamespaceEncoder();
        $object = (object) ['children' => "hello"];
        $encoder->fixSoapEncoding($object);
    }

    public function testSoapEncodingFailure2()
    {
        $this->setExpectedException();
        $encoder = self::noNamespaceEncoder();
        $object = (object) ['children' => [1, 2, 3]];
        $encoder->fixSoapEncoding($object);
    }

    public function testSoapEncodingFailure3()
    {
        $this->setExpectedException();
        $encoder = self::noNamespaceEncoder();
        $object = (object)
            [
                'children' => (object)
                    [
                        'child1' => [ 1, 2, 3],
                        'child2' => [ 1, 2, 3]
                    ]
            ];
        $encoder->fixSoapEncoding($object);
    }

    /**
     * Returns a sample XML encoder with null namespace URI
     *
     * @return CodeRage\Util\XmlEncoder
     */
    private function noNamespaceEncoder()
    {
        return new
            XmlEncoder(
                null,
                [
                    'children' => 'child',
                    'accounts' => 'account'
                ]
            );
    }

    /**
     * Returns a sample XML encoder with null namespace URI
     *
     * @return CodeRage\Util\XmlEncoder
     */
    private function namespaceEncoder()
    {
        return new
            XmlEncoder(
                "http://www.example.com",
                [
                    'children' => 'child',
                    'accounts' => 'account'
                ]
            );
    }

    /**
     * Returns an XML encoder that respects the "nil" attribute
     *
     * @return CodeRage\Util\XmlEncoder
     */
    private function xsiNilEncoder()
    {
        return new
            XmlEncoder([
                'listElements' =>
                    [
                        'children' => 'child',
                        'accounts' => 'account'
                    ],
                'xsiNilAttribute' => true
            ]);
    }

    /**
     * Returns an XML encoder that respects the "type" attribute
     *
     * @return CodeRage\Util\XmlEncoder
     */
    private function xsiTypeEncoder()
    {
        return new
            XmlEncoder([
                'listElements' =>
                    [
                        'children' => 'child',
                        'accounts' => 'account'
                    ],
                'xsiTypeAttribute' => true
            ]);
    }

    private static function complexDataStructure()
    {
        return (object)
            [
                'name' => 'Sam',
                'age' => '99.5',
                'veteran' => 'true',
                'children' =>
                    [
                        (object) [
                            'name' => 'Bob',
                            'age' => '55',
                            'veteran' => 'false',
                            'children' => []
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
    }

    private static function complexDataStructureWithNulls()
    {
        return (object)
            [
                'name' => 'Sam',
                'age' => '99.5',
                'veteran' => 'true',
                'rank' => 'captain',
                'children' =>
                    [
                        (object) [
                            'name' => 'Bob',
                            'age' => '55',
                            'veteran' => 'false',
                            'rank' => null,
                            'children' => []
                        ],
                        (object) [
                            'name' => 'Wendy',
                            'age' => '30',
                            'veteran' => 'false',
                            'rank' => null,
                        ],
                        (object) []
                    ]
            ];
    }

    private static function complexDataStructureWithTypedScalars()
    {
        return (object)
            [
                'name' => 'Sam',
                'age' => 99.5,
                'veteran' => true,
                'children' =>
                    [
                        (object) [
                            'name' => 'Bob',
                            'age' => 55,
                            'veteran' => false,
                            'children' => []
                        ],
                        (object) [
                            'name' => 'Wendy',
                            'age' => 30,
                            'veteran' => false
                        ]
                    ]
            ];
    }

    private static function complexSoapDataStructure()
    {
        return (object)
            [
                'name' => 'Sam',
                'age' => '99.5',
                'veteran' => 'true',
                'children' => (object)
                    [
                        'child' =>
                            [
                                (object) [
                                    'name' => 'Bob',
                                    'age' => '55',
                                    'veteran' => 'false',
                                    'children' => (object)
                                        [
                                            'child' => []
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
                                    'accounts' => (object)
                                        [
                                            'account' =>
                                                [
                                                    (object) [
                                                        'type' => 'checking',
                                                        'number' => '2845791',
                                                        'institution' =>
                                                            "People's Bank of Brighton Beach"
                                                    ]
                                                ]
                                        ]
                                ]
                            ]
                    ],
                'accounts' => (object)
                    [
                        'account' =>
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
                    ]
            ];
    }

    private static function complexDocument()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
             <resident>
               <name>Sam</name>
               <age>99.5</age>
               <veteran>true</veteran>
               <children>
                 <child>
                   <name>Bob</name>
                   <age>55</age>
                   <veteran>false</veteran>
                   <children/>
                 </child>
                 <child>
                   <name>Wendy</name>
                   <age>30</age>
                   <veteran>false</veteran>
                 </child>
                 <child>
                   <name>Sarah</name>
                   <age>44</age>
                   <veteran>false</veteran>
                   <accounts>
                     <account>
                       <type>checking</type>
                       <number>2845791</number>
                       <institution>People&#x27;s Bank of Brighton Beach</institution>
                     </account>
                   </accounts>
                 </child>
               </children>
               <accounts>
                 <account>
                   <type>checking</type>
                   <number>2893742</number>
                   <institution>Nevada Famers Lending</institution>
                 </account>
                 <account>
                   <type>savings</type>
                   <number>1934783</number>
                   <institution>People&#x27;s Bank of Brighton Beach</institution>
                 </account>
               </accounts>
             </resident>';
        $dom = Xml::loadDocumentXml($xml);
        $dom->normalizeDocument();
        return $dom->saveXml();
    }

    private static function complexDocumentWithNamespace()
    {
        $doc = self::complexDocument();
        $doc =
            preg_replace(
                '/<resident>/',
                '<resident xmlns="http://www.example.com">',
                $doc
            );
        return $doc;
    }
}
