<?php

/**
 * Test suite for CodeRage\Util\Test\JsonSuite
 *
 * File:        CodeRage/Util/Test/JsonSuite.php
 * Date:        Wed Feb  5 05:18:23 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CodeRage
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use CodeRage\Error;
use CodeRage\Test\Assert;
use CodeRage\Util\Json;
use CodeRage\Util\Random;


/**
 * Test suite for the CodeRage\Util\Json
 */
final class JsonSuite extends \CodeRage\Test\ReflectionSuite  {

    /**
     * A string that is not valid UTF-8
     *
     * @var string
     */
    const ISO_8859_1 = "Verkl\xE4rte Nacht";

    /**
     * @var string
     */
    const SAMPLE_SCHEMA_PATH = __DIR__ . '/Json/schema.json';

    /**
     * Constructs an instance of CodeRage\Util\Test\Base62
     */
    public function __construct()
    {
        parent::__construct(
            "Json Test Suite",
            "Tests the class CodeRage\Util\Jsons"
        );
    }

    public function testEncode1()
    {
        $this->checkEncode([], '[]');
    }

    public function testEncode2()
    {
        $this->checkEncode([3, 1, 4, 1.0, 5.9], '[3,1,4,1,5.9]');
    }

    public function testEncode3()
    {
        // This relies on associative array key order preservation
        $this->checkEncode(
            (object)['hello' => 'goodbye', 7 => -1000.9],
            '{"hello":"goodbye","7":-1000.9}'
        );
    }

    /**
     * Tests pretty printing
     */
    public function testEncode4()
    {
        $encoding = Json::encode([3, 1, 4, 1.0, 5.9], ['pretty' => true]);
        Assert::isTrue(
            strpos($encoding, "\n") !== false,
            'Encoding contains no line breaks'
        );
        $encoding = preg_replace('/\s+/', '', $encoding);
        $this->checkEncode([3, 1, 4, 1.0, 5.9], $encoding);
    }

    /**
     * Tests using the flags option
     */
    public function testEncode5()
    {
        $iso88591 = self::ISO_8859_1;
        $ascii = preg_replace('/[^ -~]/', '', $iso88591);
        $this->checkEncode(
            (object)['hello' => $iso88591],
            "{\"hello\":\"$ascii\"}",
            ['flags' =>  JSON_INVALID_UTF8_IGNORE]
        );
    }

    /**
     * Tests successful encoding with throwOnError set to true
     */
    public function testEncode6()
    {
        $this->checkEncode([], '[]', ['throwOnError' =>  true]);
    }

    /**
     * Tests successful encoding with pretty explicitly set to false
     */
    public function testEncode7()
    {
        $this->checkEncode([3, 1, 4, 1.0, 5.9], '[3,1,4,1,5.9]', [
            'pretty' => false
        ]);
    }

    public function testEncodeErrorCode1()
    {
        $this->assertError(Json::encode(INF));
    }

    public function testEncodeErrorCode2()
    {
        $this->assertError(Json::encode(NAN));
    }

    public function testEncodeErrorCode3()
    {
        $this->assertError(Json::encode(STDOUT));
    }

    /**
     * Tests atempting to encode a recusive structure
     */
    public function testEncodeErrorCode4()
    {
        $b = null;
        $a = (object) ['child' => &$b];
        $b = (object) ['child' => $a];
        $this->assertError(Json::encode($a));
    }

    /**
     * Tests atempting to encode a deeply nested structure
     */
    public function testEncodeErrorCode5()
    {
        $node = null;
        for ($i = 0; $i < 1000; ++$i)
            $node = (object) ['child' => $node];
        $this->assertError(Json::encode($node));
    }

    /**
     * Tests atempting to encode a string contaning invalid UTF-8
     */
    public function testEncodeErrorCode6()
    {
        $this->assertError(Json::encode(self::ISO_8859_1));
    }

    /**
     * Tests an encoding error with throwOnError explcitly set to false
     */
    public function testEncodeErrorCode7()
    {
        $this->assertError(Json::encode(INF, ['throwOnError' => false]));
    }

    public function testEncodeInvalidThrowOnErrorFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], ['throwOnError' => "Hello"]);
    }

    public function testEncodeInvalidThrowOnErrorFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], ['throwOnError' => 0]);
    }

    public function testEncodeInvalidThrowOnErrorFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], ['throwOnError' => 1]);
    }

    public function testEncodeInvalidPrettyFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], ['pretty' => "Hello"]);
    }

    public function testEncodeInvalidPrettyFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], ['pretty' => 0]);
    }

    public function testEncodeInvalidPrettyFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], ['pretty' => 1]);
    }

    public function testEncodeInvalidFlagsFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], ['flags' => "hello"]);
    }

    public function testEncodeInvalidFlagsFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], [
            'flags' => JSON_THROW_ON_ERROR
        ]);
    }

    public function testEncodeInvalidFlagsFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], [
            'flags' => JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING
        ]);
    }

    public function testEncodeInvalidFlagsFailure4()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], [
            'flags' => JSON_PRETTY_PRINT
        ]);
    }

    public function testEncodeInvalidFlagsFailure5()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode([], [
            'flags' => JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING
        ]);
    }

    public function testEncodeEncodingErrorFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode(INF, ['throwOnError' => true]);
    }

    public function testEncodeEncodingErrorFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode(NAN, ['throwOnError' => true]);
    }

    public function testEncodeEncodingErrorFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::encode(STDOUT, ['throwOnError' => true]);
    }

    /**
     * Tests atempting to encode a recusive structure
     */
    public function testEncodeEncodingErrorFailure4()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $b = null;
        $a = (object) ['child' => &$b];
        $b = (object) ['child' => $a];
        Json::encode($a, ['throwOnError' => true]);
    }

    /**
     * Tests atempting to encode a deeply nested structure
     */
    public function testEncodeEncodingErrorFailure5()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $node = null;
        for ($i = 0; $i < 1000; ++$i)
            $node = (object) ['child' => $node];
        Json::encode($node, ['throwOnError' => true]);
    }

    /**
     * Tests atempting to encode a string contaning invalid UTF-8
     */
    public function testEncodeEncodingErrorFailure6()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $this->assertError(Json::encode(self::ISO_8859_1, [
            'throwOnError' => true
        ]));
    }

    public function testDecode1()
    {
        $this->checkDecode('[]', []);
    }

    public function testDecode2()
    {
        $this->checkDecode('[3, 1, 4, 1.0, 5.9]', [3, 1, 4, 1.0, 5.9]);
    }

    public function testDecode3()
    {
        $this->checkDecode(
            '{"hello":"goodbye", "7" : 1000.9}',
            (object)['hello' => 'goodbye', 7 => 1000.9]
        );
    }

    public function testDecode4()
    {
        $this->checkDecode(
            '[3, 1, 4, 1.0, 5.9, {"hello" : "goodbye"}]',
            [3, 1, 4, 1.0, 5.9, (object)['hello' => 'goodbye']]
        );
    }

    /**
     * Tests decoding with objectsAsArrays set to true
     */
    public function testDecode5()
    {
        $this->checkDecode(
            '[3, 1, 4, 1.0, 5.9, {"hello" : "goodbye"}]',
            [3, 1, 4, 1.0, 5.9, ['hello' => 'goodbye']],
            ['objectsAsArrays' => true]
        );
    }

    /**
     * Tests decoding with objectsAsArrays explicitly set to false
     */
    public function testDecode6()
    {
        $this->checkDecode(
            '[3, 1, 4, 1.0, 5.9, {"hello" : "goodbye"}]',
            [3, 1, 4, 1.0, 5.9, (object)['hello' => 'goodbye']],
            ['objectsAsArrays' => false]
        );
    }

    public function testDecodeSchemaDoc()
    {
        $path = __DIR__ . '/Json/schema.json';
        $doc = json_decode(file_get_contents($path));
        Json::decode('{"firstName":"Bob"}', ['schemaDoc' => $doc]);
    }

    public function testDecodeSchemaPath()
    {
        $path = __DIR__ . '/Json/schema.json';
        Json::decode('{"firstName":"Bob"}', ['schemaPath' => $path]);
    }

    public function testDecodeSchemaString()
    {
        $path = __DIR__ . '/Json/schema.json';
        $string = file_get_contents($path);
        Json::decode('{"firstName":"Bob"}', ['schemaString' => $string]);
    }

    /**
     * Tests using the flags option
     */
    public function testDecode7()
    {
        $iso88591 = self::ISO_8859_1;
        $ascii = preg_replace('/[^ -~]/', '', $iso88591);
        $this->checkDecode("[\"$iso88591\"]", [$ascii], [
            'flags' => JSON_INVALID_UTF8_IGNORE
        ]);
        $this->assertError(Json::decode("[\"$iso88591\"]"));
    }

    public function testDecodeErrorCode1()
    {
        $this->assertError(Json::decode('[3, 1, 4, 1.0, 5.9'));
    }

    public function testDecodeErrorCode2()
    {
        $this->assertError(Json::decode("{'hello':'goodbye'}"));
    }

    public function testDecodeErrorCode3()
    {
        $this->assertError(Json::decode('{hello:"goodbye"}'));
    }

    public function testDecodeErrorCode4()
    {
        $iso88591 = self::ISO_8859_1;
        $this->assertError(Json::decode("[\"$iso88591\"]"));
    }

    /**
     * Tests a decoding error with throwOnError explcitly set to false
     */
    public function testDecodeErrorCode5()
    {
        $this->assertError(Json::decode('[', ['throwOnError' => false]));
    }

    public function testDecodeInvalidThrowOnErrorFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', ['throwOnError' => "Hello"]);
    }

    public function testDecodeInvalidThrowOnErrorFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', ['throwOnError' => 0]);
    }

    public function testDecodeInvalidThrowOnErrorFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', ['throwOnError' => 1]);
    }

    public function testDecodeInvalidObjectsAsArraysFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', ['objectsAsArrays' => "Hello"]);
    }

    public function testDecodeInvalidObjectsAsArraysFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', ['objectsAsArrays' => 0]);
    }

    public function testDecodeInvalidObjectsAsArraysFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', ['objectsAsArrays' => 1]);
    }

    public function testDecodeInvalidFlagsFailure1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', ['flags' => "hello"]);
    }

    public function testDecodeInvalidFlagsFailure2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', [
            'flags' => JSON_THROW_ON_ERROR
        ]);
    }

    public function testDecodeInvalidFlagsFailure3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', [
            'flags' => JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING
        ]);
    }

    public function testDecodeInvalidSchemaDocFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', [
            'schemaDoc' => []
        ]);
    }

    public function testDecodeInvalidSchemaPathFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', [
            'schemaDoc' => []
        ]);
    }

    public function testDecodeNonExistentSchemaPathFailure()
    {
        $this->setExpectedStatusCode('OBJECT_DOES_NOT_EXIST');
        Json::decode('{}', [
            'schemaPath' => Random::string(50)
        ]);
    }

    public function testDecodeInvalidSchemaStringFailure()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{}', [
            'schemaString' => 9.6
        ]);
    }

    public function testDecodeInconsistentSchemaOptionsFailure1()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        Json::decode('{}', [
            'schemaValidate' => true
        ]);
    }

    public function testDecodeInconsistentSchemaOptionsFailure2()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $schemaPath = self::SAMPLE_SCHEMA_PATH;
        $schemaDoc = json_decode(file_get_contents($schemaPath));
        Json::decode('{}', [
            'schemaDoc' => $schemaDoc,
            'schemaPath' => $schemaPath
        ]);
    }

    public function testDecodeInconsistentSchemaOptionsFailure3()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $schemaPath = self::SAMPLE_SCHEMA_PATH;
        $schemaString = file_get_contents($schemaPath);
        $schemaDoc = json_decode($schemaString);
        Json::decode('{}', [
            'schemaDoc' => $schemaDoc,
            'schemaPath' => $schemaString
        ]);
    }

    public function testDecodeInconsistentSchemaOptionsFailure4()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $schemaPath = self::SAMPLE_SCHEMA_PATH;
        $schemaString = file_get_contents($schemaPath);
        Json::decode('{}', [
            'schemaPath' => $schemaPath,
            'schemaString' => $schemaString
        ]);
    }

    public function testDecodeInconsistentSchemaOptionsFailure5()
    {
        $this->setExpectedStatusCode('INCONSISTENT_PARAMETERS');
        $schemaPath = self::SAMPLE_SCHEMA_PATH;
        $schemaString = file_get_contents($schemaPath);
        $schemaDoc = json_decode($schemaString);
        Json::decode('{}', [
            'schemaDoc' => $schemaDoc,
            'schemaPath' => $schemaPath,
            'schemaString' => $schemaString
        ]);
    }

    public function testDecodeDecodingError1()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('[3, 1, 4, 1.0, 5.9', [
            'throwOnError' => true
        ]);
    }

    public function testDecodeDecodingError2()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode("{'hello':'goodbye'}", [
            'throwOnError' => true
        ]);
    }

    public function testDecodeDecodingError3()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        Json::decode('{hello:"goodbye"}', [
            'throwOnError' => true
        ]);
    }

    public function testDecodeDecodingError4()
    {
        $this->setExpectedStatusCode('INVALID_PARAMETER');
        $iso88591 = self::ISO_8859_1;
        Json::decode("[\"$iso88591\"]", [
            'throwOnError' => true
        ]);
    }

    public function testValidationFailureMalformedSchema1()
    {
        $this->setExpectedStatusCode('UNEXPECTED_CONTENT');
        $path = __DIR__ . '/Json/malformed-schema.json';
        Json::decode('[]', ['schemaPath' => $path]);
    }

    public function testValidationFailureMalformedSchema2()
    {
        $this->setExpectedStatusCode('UNEXPECTED_CONTENT');
        $path = __DIR__ . '/Json/malformed-schema.json';
        $string = file_get_contents($path);
        Json::decode('[]', ['schemaString' => $string]);
    }

    public function testValidationFailureInvalidSchema1()
    {
        $this->setExpectedStatusCode('UNEXPECTED_CONTENT');
        $path = __DIR__ . '/Json/invalid-schema.json';
        Json::decode('[]', ['schemaPath' => $path]);
    }

    public function testValidationFailureInvalidSchema2()
    {
        $this->setExpectedStatusCode('UNEXPECTED_CONTENT');
        $path = __DIR__ . '/Json/invalid-schema.json';
        $string = file_get_contents($path);
        Json::decode('[]', ['schemaString' => $string]);
    }

    public function testValidationFailureInvalidDocument1()
    {
        $this->setExpectedStatusCode('UNEXPECTED_CONTENT');
        $path = __DIR__ . '/Json/schema.json';
        $doc = json_decode(file_get_contents($path));
        Json::decode('[]', ['schemaDoc' => $doc]);
    }

    public function testValidationFailureInvalidDocument2()
    {
        $this->setExpectedStatusCode('UNEXPECTED_CONTENT');
        $path = __DIR__ . '/Json/schema.json';
        $doc = json_decode(file_get_contents($path));
        Json::decode('{"firstName":0}', ['schemaDoc' => $doc]);
    }

    public function testValidationFailureInvalidDocument3()
    {
        $this->setExpectedStatusCode('UNEXPECTED_CONTENT');
        $path = __DIR__ . '/Json/schema.json';
        Json::decode('[]', ['schemaPath' => $path]);
    }

    public function testValidationFailureInvalidDocument4()
    {
        $this->setExpectedStatusCode('UNEXPECTED_CONTENT');
        $path = __DIR__ . '/Json/schema.json';
        $string = file_get_contents($path);
        Json::decode('{"firstName":0}', ['schemaString' => $string]);
    }

    /**
     * Throws an exception if the result of encoding the given value is not
     * equal to the expected value
     *
     * @param mixed $value The value to encode
     * @param string $expected The expected encoded value
     * @param array $options The encoding options
     * @throws CodeRage\Error
     */
    private function checkEncode($value, $expected, array $options = [])
    {
        Assert::equal(Json::encode($value, $options), $expected);
    }

    /**
     * Throws an exception if the result of decoding the given string is not
     * equal to the expected value
     *
     * @param string $value The string to decode
     * @param mixed $expected The expected decoded value
     * @param array $options The decoding options
     * @throws CodeRage\Error
     */
    private function checkDecode($value, $expected, array $options = [])
    {
        Assert::equal(Json::decode($value, $options), $expected);
    }

    /**
     * Throws an exception if the given value is not equal to
     * CodeRaGe\Util\Json::ERROR
     *
     * @param mixed $value
     * @throws CodeRage\Error
     */
    private function assertError($value)
    {
        if ($value !== Json::ERROR)
            throw new
                Error([
                    'status' => 'ASSERTION_FAILURE',
                    'message' =>
                        "Expected CodeRage\Util\Json::ERROR; found " .
                        Error::formatValue($value)
                ]);
    }
}
