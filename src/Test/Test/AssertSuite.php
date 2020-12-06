<?php

/**
 * Defines the class CodeRage\Test\Test\AssertSuite
 *
 * File:        CodeRage/Test/Test/AssertSuite.php
 * Date:        Tue Mar 17 11:22:16 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test\Test;

use stdClass;
use CodeRage\Test\Assert;

/**
 * @ignore
 */

/**
 * Test suite for the class CodeRage\Test\Assert
 */
class AssertSuite extends \CodeRage\Test\ReflectionSuite {

    /**
     * Constructs an instance of CodeRage\Test\Test\AssertSuite.
     */
    public function __construct()
    {
        parent::__construct(
            "Assert Suite",
            "Tests for the class CodeRage\Test\Assert"
        );
    }

    public function testIsTrue1()
    {
        Assert::isTrue(true);
    }

    public function testIsTrue2()
    {
        Assert::isTrue(1);
    }

    public function testIsTrue3()
    {
        Assert::isTrue(0.00001);
    }

    public function testIsTrue4()
    {
        Assert::isTrue([0]);
    }

    public function testIsTrue5()
    {
        Assert::isTrue(new stdClass);
    }

    public function testIsTrue6()
    {
        Assert::isTrue("00");
    }

    public function testIsTrue7()
    {
        Assert::isTrue(STDOUT);
    }

    public function testIsTrueFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isTrue(false);
    }

    public function testIsTrueFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isTrue(0);
    }

    public function testIsTrueFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isTrue(0.0);
    }

    public function testIsTrueFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isTrue("0");
    }

    public function testIsTrueFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isTrue([]);
    }

    public function testIsFalse1()
    {
        Assert::isFalse(false);
    }

    public function testIsFalse2()
    {
        Assert::isFalse(0);
    }

    public function testIsFalse3()
    {
        Assert::isFalse(0.0);
    }

    public function testIsFalse4()
    {
        Assert::isFalse("0");
    }

    public function testIsFalse5()
    {
        Assert::isFalse([]);
    }

    public function testIsFalseFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFalse(true);
    }

    public function testIsFalseFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFalse(1);
    }

    public function testIsFalseFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFalse(0.00001);
    }

    public function testIsFalseFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFalse([0]);
    }

    public function testIsFalseFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFalse(new stdClass);
    }

    public function testIsFalseFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFalse("00");
    }

    public function testIsFalseFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFalse(STDOUT);
    }

    public function testIsBool1()
    {
        Assert::isBool(true);
    }

    public function testIsBool2()
    {
        Assert::isBool(false);
    }

    public function testIsBoolFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isBool(0);
    }

    public function testIsBoolFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isBool(1);
    }

    public function testIsBoolFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isBool(0.0);
    }

    public function testIsBoolFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isBool("");
    }

    public function testIsBoolFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isBool(null);
    }

    public function testIsBoolFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isBool([]);
    }

    public function testIsBoolFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isBool(new stdClass);
    }

    public function testIsBoolFail8()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isBool(STDOUT);
    }

    public function testIsInt1()
    {
        Assert::isInt(-1009);
    }

    public function testIsInt2()
    {
        Assert::isInt(PHP_INT_MAX);
    }

    public function testIsIntFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isInt(true);
    }

    public function testIsIntFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isInt(1.0);
    }

    public function testIsIntFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isInt("100000");
    }

    public function testIsIntFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isInt(null);
    }

    public function testIsIntFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isInt([]);
    }

    public function testIsIntFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isInt(new stdClass);
    }

    public function testIsIntFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isInt(STDOUT);
    }

    public function testIsFloat1()
    {
        Assert::isFloat(0.000228371);
    }

    public function testIsFloat2()
    {
        Assert::isFloat(INF);
    }

    public function testIsFloat3()
    {
        Assert::isFloat(-INF);
    }

    public function testIsFloat4()
    {
        Assert::isFloat(NAN);
    }

    public function testIsFloat5()
    {
        Assert::isFloat(PHP_FLOAT_MAX);
    }

    public function testIsFloatFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFloat(true);
    }

    public function testIsFloatFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFloat(-1009);
    }

    public function testIsFloatFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFloat("100000");
    }

    public function testIsFloatFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFloat(null);
    }

    public function testIsFloatFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFloat([]);
    }

    public function testIsFloatFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFloat(new stdClass);
    }

    public function testIsFloatFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isFloat(STDOUT);
    }

    public function testIsNumeric1()
    {
        Assert::isNumeric(-1009);
    }

    public function testIsNumeric2()
    {
        Assert::isNumeric(0.000228371);
    }

    public function testIsNumeric3()
    {
        Assert::isNumeric("-1009");
    }

    public function testIsNumeric4()
    {
        Assert::isNumeric("0.000228371");
    }

    public function testIsNumeric5()
    {
        Assert::isNumeric("2.28371E-8");
    }

    public function testIsNumeric6()
    {
        Assert::isNumeric("001");
    }

    public function testIsNumericFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNumeric(true);
    }

    public function testIsNumericFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNumeric("five");
    }

    public function testIsNumericFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNumeric(null);
    }

    public function testIsNumericFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNumeric([]);
    }

    public function testIsNumericFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNumeric(new stdClass);
    }

    public function testIsNumericFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNumeric(STDOUT);
    }

    public function testIsNumericFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNumeric("0b01");
    }

    public function testIsNumericFail8()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNumeric("0xfe");
    }

    public function testIsString()
    {
        Assert::isString("Hello");
    }

    public function testIsStringFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isString(true);
    }

    public function testIsStringFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isString(-1009);
    }

    public function testIsStringFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isString(0.000228371);
    }

    public function testIsStringFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isString(null);
    }

    public function testIsStringFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isString([]);
    }

    public function testIsStringFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isString(new stdClass);
    }

    public function testIsStringFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isString(STDOUT);
    }

    public function testIsArray1()
    {
        Assert::isArray([]);
    }

    public function testIsArray2()
    {
        Assert::isArray([1, 3, 5, 7, 9]);
    }

    public function testIsArray3()
    {
        Assert::isArray([1 => 2, 3 => 4, 5 => 6]);
    }

    public function testIsArrayFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isArray(true);
    }

    public function testIsArrayFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isArray(-1009);
    }

    public function testIsArrayFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isArray("100000");
    }

    public function testIsArrayFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isArray(0.000228371);
    }

    public function testIsArrayFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isArray(null);
    }

    public function testIsArrayFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isArray(new stdClass);
    }

    public function testIsArrayFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isArray(STDOUT);
    }

    public function testIsObject1()
    {
        Assert::isObject(new stdClass);
    }

    public function testIsObject2()
    {
        Assert::isObject(new \DateTime);
    }

    public function testIsObjectFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isObject(true);
    }

    public function testIsObjectFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isObject(-1009);
    }

    public function testIsObjectFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isObject("100000");
    }

    public function testIsObjectFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isObject(0.000228371);
    }

    public function testIsObjectFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isObject(null);
    }

    public function testIsObjectFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isObject([]);
    }

    public function testIsObjectFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isObject(STDOUT);
    }

    public function testIsNull()
    {
        Assert::isNull(null);
    }

    public function testIsNullFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNull(false);
    }

    public function testIsNullFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNull(0);
    }

    public function testIsNullFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNull(0.0);
    }

    public function testIsNullFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNull(NAN);
    }

    public function testIsNullFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNull('');
    }

    public function testIsNullFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNull([]);
    }

    public function testIsNullFail7()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::isNull(new stdClass);
    }

    public function testTypeMismatch1()
    {
        $actual = true;
        $expected = 9.0;
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testTypeMismatch2()
    {
        $actual = true;
        $expected = "true";
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testTypeMismatch3()
    {
        $actual = true;
        $expected = [1, 2, 3];
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testTypeMismatch4()
    {
        $actual = true;
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b'
            ];
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testTypeMismatch5()
    {
        $actual = [1, 2, 3];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b'
            ];
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testTypeMismatch6()
    {
        $actual = true;
        $expected = new \Exception;
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testDifferentBooleans()
    {
        $actual = true;
        $expected = false;
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testDifferentIntegers()
    {
        $actual = 1;
        $expected = -9;
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testDifferentStrings()
    {
        $actual = "wrong";
        $expected = "right";
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testDifferentLengthArrays()
    {
        $actual = [1, 2, 3, 4];
        $expected = [1, 2, 3, 4, 5];
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testDifferentArraysItems1()
    {
        $actual = [1, 2, "wrong", 4];
        $expected = [1, 2, "right", 4];
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testDifferentNumberOfProperties()
    {
        $actual = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b'
            ];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b',
                'c' => 'd'
            ];
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testDifferentPropertyValues()
    {
        $actual = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b',
                'c' => 'd'
            ];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => 'bb',
                'c' => 'd'
            ];
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testDifferentComplexDataStructures1()
    {
        $actual = (object)
            [
                'hello' => 'goodbye',
                'a' => [1, 2, 3],
                'c' => (object)
                    [
                        'please' => 9,
                        'thanks' => -0.04
                    ]
            ];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => [1, 2, 3],
                'c' => (object)
                    [
                        'please' => 9,
                        'thanks' => -0.04
                    ]
            ];
        Assert::equal($actual, $expected);
        Assert::equal($expected, $actual);
    }

    public function testDifferentComplexDataStructures2()
    {
        $actual = (object)
            [
                'hello' => 'goodbye',
                'a' => [1, 2, 3],
                'c' => (object)
                    [
                        'please' => 9,
                        'thanks' => -0.04
                    ]
            ];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => [1, 2, 3, 4],
                'c' => (object)
                    [
                        'please' => 9,
                        'thanks' => -0.04
                    ]
            ];
        Assert::notEqual($actual, $expected);
        Assert::notEqual($expected, $actual);
    }

    public function testEqualBooleans()
    {
        Assert::equal(true, true);
        Assert::equal(false, false);
    }

    public function testEqualInts()
    {
        Assert::equal(5, 5);
    }

    public function testEqualListsOfInts()
    {
        $list = [3, 1, 4, -1, 5, 9, 0];
        Assert::equal($list, $list);
    }

    public function testEqualListsOfStrings()
    {
        $list = ['hello', 'goodbye', 'thanks'];
        Assert::equal($list, $list);
    }

    public function testEqualListsOfScalars()
    {
        $list = [1, 'hello', -99, false, 10000, null, 'goodbye'];
        Assert::equal($list, $list);
    }

    public function testEqualMapsOfInts()
    {
        $map = ['a' => 1, 'b' => -99, 'c' => 5];
        Assert::equal($map, $map);
    }

    public function testEqualMapsOfStrings()
    {
        $map = ['a' => 'hello', 'b' => 'goodbye', 'c' => 'thanks'];
        Assert::equal($map, $map);
    }

    public function testEqualTypeMismatchFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = true;
        $expected = 9.0;
        Assert::equal($actual, $expected);
    }

    public function testEqualTypeMismatchFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = true;
        $expected = "true";
        Assert::equal($actual, $expected);
    }

    public function testEqualTypeMismatchFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = true;
        $expected = [1, 2, 3];
        Assert::equal($actual, $expected);
    }

    public function testEqualTypeMismatchFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = true;
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b'
            ];
        Assert::equal($actual, $expected);
    }

    public function testEqualTypeMismatchFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = [1, 2, 3];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b'
            ];
        Assert::equal($actual, $expected);
    }

    public function testEqualTypeMismatchFail6()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = true;
        $expected = new \Exception;
        Assert::equal($actual, $expected);
    }

    public function testEqualDifferentBooleansFail()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = true;
        $expected = false;
        Assert::equal($actual, $expected);
    }

    public function testEqualDifferentIntegersFail()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = 1;
        $expected = -9;
        Assert::equal($actual, $expected);
    }

    public function testEqualDifferentFloatsFail()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = 1.000001;
        $expected = -9.6;
        Assert::equal($actual, $expected);
    }

    public function testDifferentStringsFail()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = "wrong";
        $expected = "right";
        Assert::equal($actual, $expected);
    }

    public function testDifferentLengthArraysFail()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = [1, 2, 3, 4];
        $expected = [1, 2, 3, 4, 5];
        Assert::equal($actual, $expected);
    }

    public function testEqualDifferentArraysItemsFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = [1, 2, "wrong", 4];
        $expected = [1, 2, "right", 4];
        Assert::equal($actual, $expected);
    }

    public function testDifferentNumberOfPropertiesFail()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b'
            ];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b',
                'c' => 'd'
            ];
        Assert::equal($actual, $expected);
    }

    public function testDifferentPropertyValuesFail()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = (object)
            [
                'hello' => 'goodbye',
                'a' => 'b',
                'c' => 'd'
            ];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => 'bb',
                'c' => 'd'
            ];
        Assert::equal($actual, $expected);
    }

    public function testEqualDifferentComplexDataStructuresFail()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        $actual = (object)
            [
                'hello' => 'goodbye',
                'a' => [1, 2, 3],
                'c' => (object)
                    [
                        'please' => 9,
                        'thanks' => -0.04
                    ]
            ];
        $expected = (object)
            [
                'hello' => 'goodbye',
                'a' => [1, 2, 3, 4],
                'c' => (object)
                    [
                        'please' => 9,
                        'thanks' => -0.04
                    ]
            ];
        Assert::equal($actual, $expected);
    }

    public function testAlmostEqual()
    {
        Assert::almostEqual(1, -1, 2.001);
        Assert::almostEqual(0.025863, 0.025901, 0.0001);
        Assert::almostEqual(1009, 1008.62, 0.5);
        Assert::almostEqual(1008.62, 1009, 0.5);
        Assert::almostEqual(1008.025901, 1008.025863, 1);
    }

    public function testAlmostEqualFail1()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::almostEqual([], -1, 2.001);
    }

    public function testAlmostEqualFail2()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::almostEqual(1, [], 2.001);
    }

    public function testAlmostEqualFail3()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::almostEqual(1, -1, []);
    }

    public function testAlmostEqualFail4()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::almostEqual(1, -1, 1);
    }

    public function testAlmostEqualFail5()
    {
        $this->setExpectedStatusCode('ASSERTION_FAILED');
        Assert::almostEqual(1008.025863, 1009.025901, 1);
    }

    public function testEqualComplexDataStructures()
    {
        $data = (object)
            [
                'hello' => 'goodbye',
                'a' => [false, 1, '2', 3.0, null],
                'c' => (object)
                    [
                        'please' => 9,
                        'thanks' => -0.04,
                        'bitte' => null,
                        'danke' => true
                    ]
            ];
        Assert::equal($data, $data);
    }

    public function testDifferentElementNamespaces1()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello/>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello xmlns="http://www.example.net"/>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentElementNamespaces2()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello xmlns="http://www.example.com"/>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello xmlns="http://www.example.net"/>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentElementNamespaces3()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <e:hello xmlns:e="http://www.example.com"/>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <e:hello xmlns:e="http://www.example.net"/>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentAttributeNamespaces1()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello xmlns:e="http://www.example.com" e:a="0"/>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello xmlns:e="http://www.example.net" e:a="0"/>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentAttributeNamespaces2()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello a="0"/>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello xmlns:e="http://www.example.net" e:a="0"/>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testMissingChild1()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello><goodbye/></hello>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello></hello>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testMissingChild2()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello><a/><b/><c/></hello>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello><a/><c/></hello>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testReorderedChildren()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello><a/><c/></hello>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello><a/><c/><b/></hello>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentAttributeValue1()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello a="0"/>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello a="1"/>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentAttributeValue2()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello xmlns:e="http://www.example.com" a="0"/>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <hello xmlns:e="http://www.example.com" a="1"/>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentNodeText1()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a>hello</a>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a>goodbye</a>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentNodeText2()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a>hello</a>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a></a>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentCdataSections1()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a><![CDATA[hello]]></a>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a><![CDATA[goodbye]]></a>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentTextContent1()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a>hello</a>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a><![CDATA[hello]]></a>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testDifferentTextContent2()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a><![CDATA[one]]>two<![CDATA[three]]></a>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a>one<![CDATA[two]]>three</a>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }

    public function testComplexDocument1()
    {
        $actual =
            '<?xml version="1.0" encoding="UTF-8"?>
             <a xmlns="http://www.example.com">
               <b>hello</b>
               <c xmlns="http://www.example.net" xmlns:e="http://www.example.net">
                 <one a="123">
                 <two>goodbye</two>
                 <three e:b="456"/>
               </c>
             </a>';
        $expected =
            '<?xml version="1.0" encoding="UTF-8"?>
             <e:a xmlns:e="http://www.example.com">
               <e:b>hello</e:b>
               <f:c xmlns:f="http://www.example.net">
                 <f:one a="123">
                 <f:two>goodbye</f:two>
                 <f:three f:b="456"/>
               </f:c>
             </e:a>';
        Assert::inequivalentXmlData($actual, $expected);
        Assert::inequivalentXmlData($expected, $actual);
    }
}
