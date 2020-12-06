<?php

/**
 * Defines the class CodeRage\Util\Json
 *
 * File:        CodeRage/Util/Json.php
 * Date:        Wed Jul 17 18:42:44 UTC 2019
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2019 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Throwable;
use CodeRage\Error;
use CodeRage\File;
use CodeRage\Util\Args;


/**
 * Container for static methods for JSON encoding and decoding
 */
final class Json {

    /**
     * Out-of-band value for as return values when encoding or decoding fails
     *
     * @var float
     */
    const ERROR = INF;

    /**
     * Value passed as $depth parameter to json_decode; currently not
     * configurable
     *
     * @var integer
     */
    const RECURSION_DEPTH = 512;

    /**
     * Returns the result of encoding the given value as JSON
     *
     * @param mixed $value The value to encode
     * @param array $options The options array; supports the following options:
     *     throwOnError - true to throw an exception on failure
     *     pretty - true to return pretty-printed output
     *     flags - A bitwise OR of zero or more of the constants JSON_XXX,
     *       excluding JSON_THROW_ON_ERROR and JSON_PRETTY_PRINT
     * @return mixed The encoded value, or the value of the ERROR constant if
     *   an error occurred and $throwOnError is false
     * @throws CodeRage\Error if an error occurs and $throwOnError is true
     */
    static function encode($value, array $options = [])
    {
        $throwOnError =
            Args::checkKey($options, 'throwOnError', 'boolean', [
                'label' => 'throw on error flag',
                'default' => false
            ]);
        $flags = self::processFlags($options, true);
        $json = json_encode($value, $flags);
        if ($json === false && $throwOnError)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => 'JSON encoding error',
                    'details' => 'JSON encoding error: ' . self::lastError()
                ]);
        return $json !== false ? $json : self::ERROR;
    }

    /**
     * Returns the result of decoding the given JSON string
     *
     * @param string $json The string to decode
     * @param array $options The options array; supports the following options:
     *     throwOnError - true to throw an exception on failure; defaults to
     *       false
     *     objectsAsArrays - true to decode epressions represent objects as
     *       array; defaults to false
     *     flags - A bitwise OR of zero or more of the constants JSON_XXX,
     *       excluding JSON_THROW_ON_ERROR
     *     schemaDoc - A PHP object to be interpreted as a parsed JSON Schema
     *       document (optional)
     *     schemaPath - The path to a JSON Schema document (optional)
     *     schemaString - A string whose content is a JSON Schema document
     *       (optional)
     *     schemaValidate - true to validate the schema; defaults to true
     *   At most one of "schemaDoc", "schemaPath", and "schemaString" may be
     *   supplied
     * @return mixed The decoded value, or the value of the ERROR constant if an
     *   error occurred and $throwOnError is false
     * @throws CodeRage\Error if an error occurs and $throwOnError is true
     */
    static function decode($json, array $options = [])
    {
        $throwOnError =
            Args::checkKey($options, 'throwOnError', 'boolean', [
                'label' => 'throw on error flasg',
                'default' => false
            ]);
        $objectsAsArrays =
            Args::checkKey($options, 'objectsAsArrays', 'boolean', [
                'label' => 'objects as arrays',
                'default' => false
            ]);
        $flags = self::processFlags($options, false);
        $schema = self::processSchema($options);
        $value =
            json_decode($json, $objectsAsArrays, self::RECURSION_DEPTH, $flags);
        $result = $value !== null || trim($json) === 'null' ?
            $value :
            self::ERROR;
        if ($result === self::ERROR && $throwOnError)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => 'JSON encoding error',
                    'details' => 'JSON encoding error: ' . self::lastError()
                ]);
        if ($schema !== nulL) {
            $validator = new \JsonSchema\Validator;
            $constraints =
                Constraint::CHECK_MODE_EXCEPTIONS |
                ( $options['schemaValidate'] ?
                      Constraint::CHECK_MODE_VALIDATE_SCHEMA :
                      0 );
            try {
                $validator->validate($result, $schema, $constraints);
            } catch (Throwable $e) {
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' => $e->getMessage(),
                        'inner' => $e
                    ]);
            }
        }
        return $result;
    }

    /**
     * Returns the most recent JSON encoding or decoding error, as a string
     *
     * @return string The error message, beginning with a lowercase letter
     */
    static function lastError()
    {
        $msg = json_last_error_msg();
        return $msg !== false && json_last_error() !== JSON_ERROR_NONE ?
            lcfirst($msg) :
            'unknown error';
    }

    /**
     * Returns a bitwise OR of the constants JSON_XXX, suitable for passing to
     * json_encode and json_decode
     *
     * @param array $options The options array; supports the following options:
     *     pretty - true to return pretty-printed output
     *     flags - A bitwise OR of zero or more of the constants JSON_XXX,
     *       excluding JSON_THROW_ON_ERROR
     * @param boolean $encode true if the flags are be to passed to
     *   json_encode() rather then json_decode()
     * @return int
     */
    private function processFlags(array $options, bool $encode)
    {
        $flags = Args::checkKey($options, 'flags', 'int');
        if (($flags & JSON_THROW_ON_ERROR) != 0)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "The flag JSON_THROW_ON_ERROR is not supported; use " .
                        "the option 'throwOnError' instead"
                ]);
        if ($encode) {
            if (($flags & JSON_PRETTY_PRINT) != 0)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            "The flag JSON_PRETTY_PRINT is not supported; " .
                            "use the option 'pretty' instead"
                    ]);
            $pretty =
                Args::checkKey($options, 'pretty', 'boolean', [
                    'label' => 'pretty flag',
                    'default' => false
                ]);
            if ($pretty)
                $flags |= JSON_PRETTY_PRINT;
            $flags |= JSON_UNESCAPED_SLASHES;
        }
        return $flags;
    }

    /**
     * Returns a PHP object, to be interpreted as a parsed JSON Schema document,
     * or null if none of the schema options is supplied
     *
     * @param array $options The options array; supports the following options:
     *     schemaDoc - A PHP object to be interpreted as a parsed JSON Schema
     *       document (optional)
     *     schemaPath - The path to a JSON Schema document (optional)
     *     schemaString - A string whose content is a JSON Schema document
     *       (optional)
     *   At most one of "schemaDoc", "schemaPath", and "schemaString" may be
     *   supplied
     * @return object
     */
    private static function processSchema(array &$options)
    {
        $hasDoc = isset($options['schemaDoc']);
        $hasPath = isset($options['schemaPath']);
        $hasString = isset($options['schemaString']);
        $hasValidate = isset($options['schemaValidate']);
        $count = $hasDoc + $hasPath + $hasString;
        if ($count == 0) {
            if ($hasValidate)
                throw new
                    Error([
                        'status' => 'INCONSISTENT_PARAMETERS',
                        'details' =>
                            "The option 'schemaValidate' may only be used in " .
                            "combination with one of the other schema options"
                    ]);
            return null;
        }
        if ($count > 1)
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'details' =>
                        "At most one of the options 'schemaDoc', " .
                        "'schemaPath, and 'schemaString' may be supplied"
                ]);
        if (($val = Args::checkKey($options, 'schemaDoc', 'object')) !== nulL) {
            $schema = $val;
        } else {
            $schemaString = null;
            if (($val = Args::checkKey($options, 'schemaString', 'string')) !== null) {
                $schemaString = $val;
            } else {
                $val = Args::checkKey($options, 'schemaPath', 'string');
                File::checkFile($val, 0b0100);
                $schemaString = file_get_contents($val);
            }
            $schema = self::decode($schemaString);
            if ($schema === Json::ERROR)
                throw new
                    Error([
                        'status' => 'UNEXPECTED_CONTENT',
                        'details' => 'JSON decoding error: malformed schema'
                    ]);
        }
        Args::checkKey($options, 'schemaValidate', 'boolean', [
            'default' => true
        ]);
        return $schema;
    }
}
