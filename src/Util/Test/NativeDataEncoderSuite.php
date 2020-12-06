<?php

/**
 * Defines the class CodeRage\Util\Test\NativeDataEncoderSuite
 *
 * File:        CodeRage/Util/Test/NativeDataEncoderSuite.php
 * Date:        Mon May 21 13:34:26 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Error;
use CodeRage\Test\Assert;
use CodeRage\Util\NativeDataEncoder;

/**
 * @ignore
 */

/**
 * Test suite for the class CodeRage\Util\NativeDataEncoder
 */
class NativeDataEncoderSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * Constructs an instance of CodeRage\Util\Test\NativeDataEncoderSuite.
     */
    public function __construct()
    {
        parent::__construct(
            "Native Data Encoder Test Suite",
            "Tests the class CodeRage\Util\NativeDataEncoder"
        );
    }

    public function testEncoding1()
    {
        $encoder = self::createEncoder();
        $object =
            self::createPropertyBasedEncoding(
                ['age', 'rank', 'serialNumber']
            );
        $properties =
            [
                'age' => 78,
                'rank' => 'captain',
                'serialNumber' => '90-2389-22'
            ];
        $object->setProperties($properties);
        Assert::equivalentData(
            $encoder->encode($object),
            (object) $properties
        );
    }

    public function testEncoding2()
    {
        $encoder = self::createEncoder();
        $object =
            self::createPropertyBasedEncoding([
                'age' => 'getAge',
                'rank' => 'getRank',
                'serialNumber' => 'getSerialNumber'
            ]);
        $properties =
            [
                'getAge' => 78,
                'getRank' => 'captain',
                'getSerialNumber' => '90-2389-22'
            ];
        $object->setProperties($properties);
        $expected = (object)
            [
                'age' => 78,
                'rank' => 'captain',
                'serialNumber' => '90-2389-22'
            ];
        Assert::equivalentData(
            $expected,
            $encoder->encode($object)
        );
    }

    public function testEncoding3()
    {
        $encoder = self::createEncoder();
        $object =
            self::createPropertyBasedEncoding([
                'age' => self::createCallback('getAge'),
                'rank' => self::createCallback('getRank'),
                'serialNumber' => self::createCallback('getSerialNumber')
            ]);
        $properties =
            [
                'getAge' => 78,
                'getRank' => 'captain',
                'getSerialNumber' => '90-2389-22'
            ];
        $object->setProperties($properties);
        $expected = (object)
            [
                'age' => 78,
                'rank' => 'captain',
                'serialNumber' => '90-2389-22'
            ];
        Assert::equivalentData(
            $expected,
            $encoder->encode($object)
        );
    }

    public function testEncoding4()
    {
        $encoder = self::createEncoder();
        $child1 =
            self::createPropertyBasedEncoding(
                ['name', 'age']
            );
        $child1Properties =
            [
                'name' => 'Jason',
                'age' => 12
            ];
        $child1->setProperties($child1Properties);
        $child2 =
            self::createPropertyBasedEncoding(
                ['name', 'age']
            );
        $child2Properties =
            [
                'name' => 'Kelly',
                'age' => 8
            ];
        $child2->setProperties($child2Properties);
        $parent =
            self::createPropertyBasedEncoding(
                ['name', 'children']
            );
        $parentProperties =
            [
                'name' => 'Wendy',
                'children' => [$child1, $child2]
            ];
        $parent->setProperties($parentProperties);
        $expected = (object)
            [
                'name' => 'Wendy',
                'children' =>
                    [
                        (object) $child1Properties,
                        (object) $child2Properties
                    ]
            ];
        Assert::equivalentData(
            $encoder->encode($parent),
            $expected
        );
    }

    public function testEncodingFailure1()
    {
        $encoder = self::createEncoder();
        $result = [1, 2, 3];
        $object = self::createCustomEncoding($result);
        Assert::equivalentData(
            $encoder->encode($object),
            $result
        );
    }

    public function testEncoding5()
    {
        $encoder = self::createEncoder();
        $result = [1, 2, 3];
        $object = self::createCustomEncoding($result);
        Assert::equivalentData(
            $encoder->encode($object),
            $result
        );
    }

    /**
     * Returns an newly constructed native data encoder
     *
     * @param array $options An associative array of options used to customize
     *   the encoding.
     * @return CodeRage\Util\NativeDataEncoder
     */
    private static function createEncoder($options = [])
    {
        return new NativeDataEncoder($options);
    }

    /**
     * Returns an object with a nativeDataProperties() method
     *
     * @param mixed $encoding The unconditional return value of
     *   nativeDataProperties(), if any
     * @return object
     */
    private static function createPropertyBasedEncoding($properties = null)
    {
        return new
            NativeDataEncoderPropertyBasedEncoding($properties);
    }

    /**
     * Returns an object with a nativeDataEncode() method
     *
     * @param mixed $encoding The unconditional return value of
     *   nativeDataEncode(), if any
     * @return object
     */
    private static function createCustomEncoding($encoding = null)
    {
        return new NativeDataEncoderCustomEncoding($encoding);
    }

    /**
     * Returns a callback
     *
     * @param mixed $method The name of the method to be invoked on the first
     *   argument to excecute()
     * @return callable
     */
    private static function createCallback($method)
    {
        return function($o) use($method) { return $o->$method(); };
    }
}
