<?php

/**
 * Defines the class CodeRage\Util\BracketObjectNotation
 *
 * File:        CodeRage/Util/BracketObjectNotation.php
 * Date:        Thu Sep 28 21:45:24 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use stdClass;
use CodeRage\Error;
use CodeRage\Util\Args;
use CodeRage\Util\Array_;


/**
 * Implements a method for representing native data structures as a sequence of
 * assignments for the form XXX=YYY where YYY is an arbitrary string and XXX
 * and XXX is an expression representing the path of the value YYY within the
 * data structure, in a manner similar to how PHP constructs the value of the
 * variable $_GET
 */
class BracketObjectNotation {

    /**
     * @var string
     */
    const MATCH_KEY =
        '/^([_a-zA-Z0-9]+(\.[_a-zA-Z0-9]+)*|\[[0-9]+\])(\[[_a-zA-Z0-9]*\])*$/';

    /**
     * Constructs a native data structure from a list of path assignments
     *
     * @param array $assignments A list of strings of the form X=Y or pairs
     *   [X, Y] where X represents the position of the string Y within the data
     *   strcture begin constructed
     * @param array $options The options array; supports the following options:
     *     objectsAsArrays - true to use associate arrays instead of instances
     *     of stdClass when constructing the result
     * @return mixed A value composed from strings using indexed
     *   arrays and instances of stdClass, or using indexed arrays and
     *   associative arrays if the option 'objectsAsArrays' is true
     */
    public static function decode($assignments, $options = [])
    {
        $objectsAsArrays = isset($options['objectsAsArrays']) ?
            $options['objectsAsArrays'] :
            false;
        $result = null;
        foreach ($assignments as $a) {

            // Validate and parse $a
            if (is_string($a)) {
                $pos = strpos($a, '=');
                if ($pos === false)
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' => "Invalid assignment '$a': missing '='"

                        ]);
                if ($pos == 0)
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Invalid assignment '$a': missing path " .
                                "expression"
                        ]);
                $a = [substr($a, 0, $pos), substr($a, $pos + 1)];
            }
            Args::check($a, 'array', 'assignment');
            if (!Array_::isIndexed($a))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            'Invalid assignment: expected indexed array; ' .
                            'found associative array'
                    ]);
            if (count($a) != 2)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' =>
                            'Invalid assignment: expected pair; found array ' .
                            'of length ' . count($a)
                    ]);
            list($key, $value) = $a;
            if (!preg_match(self::MATCH_KEY, $key))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid key '$key': expected identifier or " .
                            "square bracket expression"
                    ]);
            $parts = preg_split('/(?<!\])\[|\]\[/', ltrim(rtrim($key, ']'), '['));

            // Initialize Result
            if ($result === null)
                $result = $key[0] == '[' ?
                    [] :
                    self::newObject($objectsAsArrays);

            // Apply the assignment $key=$value
            $node =& $result;
            for ($i = 0, $n = count($parts); $i < $n; ++$i) {
                $p = $parts[$i];
                $numeric = $p == '' || ctype_digit($p);
                if ($numeric || $objectsAsArrays) {
                    if (is_string($node) || is_object($node))
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'message' =>
                                    "Invalid assigment '$key=$value': " .
                                    "expected array as position " . ($i + 1) .
                                    "; found " .
                                    (is_object($node) ? 'object' : 'string')
                            ]);
                    if ($numeric) {
                        $len = count($node);
                        if ($p == '') {
                            $p = $len;
                        } elseif ($p > $len) {
                            throw new
                                Error([
                                    'status' => 'INVALID_PARAMETER',
                                    'message' =>
                                        "Invalid assigment '$key=$value': " .
                                        "expected array of length at least " .
                                        "$p at position " . ($i + 1) . "; " .
                                        "found array of length " . count($node)
                                ]);
                        }
                    }
                    if ($i == $n - 1) {
                        $node[$p] = $value;
                    } elseif (isset($node[$p])) {
                        $node =& $node[$p];
                    } else {
                        $nextP = $parts[$i + 1];
                        $nextNode = $nextP == '' || ctype_digit($nextP) ?
                            [] :
                            static::newObject($objectsAsArrays);
                        $node[$p] = $nextNode;
                        $node =& $node[$p];
                    }
                } else {
                    if (!is_object($node))
                        throw new
                            Error([
                                'status' => 'INVALID_PARAMETER',
                                'message' =>
                                    "Invalid assigment '$key=$value': " .
                                    "expected object as position " . ($i + 1) .
                                    "; found " .
                                    (is_array($node) ? 'array' : 'string')
                            ]);
                    if ($i == $n - 1) {
                        $node->$p = $value;
                    } elseif (isset($node->$p)) {
                        $node =& $node->$p;
                    } else {
                        $nextP = $parts[$i + 1];
                        $nextNode = $nextP == '' || ctype_digit($nextP) ?
                            [] :
                            static::newObject($objectsAsArrays);
                        $node->$p = $nextNode;
                        $node =& $node->$p;
                    }
                }
            }
        }
        if ($result === null)
            $result = new stdClass;
        return $result;
    }

    /**
     * Returns a list of assignments of the form X=Y, encoded as arrays
     * [X, Y] of strings, where X represents the position of the string Y within
     * the given data strcture
     *
     * @param mixed $object The data structure to be encoded; $object may be any
     *   value composed from strings using indexed arrays, associative arrays,
     *   and instances of stdClass
     * @return array
     */
    public static function encode($object)
    {
        Args::check($object, 'array|stdClass', 'object');
        $assignments = [];
        self::encodeImpl($assignments, $object);
        return $assignments;
    }

    /**
     * Encodes the given data structure as a URL query string
     *
     * @param mixed $object The data structure to be encoded; $object may be any
     *   value composed from strings using indexed arrays, associative arrays,
     *   and instances of stdClass
     * @return string The result of transforming the collection of assignments
     *   returned by encode() into a URL query string
     */
    public static function encodeAsQuery($object)
    {
        $result = self::encode($object);
        $func =
            function($a)
            {
                return rawurlencode($a[0]) . '=' . rawurlencode($a[1]);
            };
        return Array_::map($func, $result, '&');
    }

    /**
     * Helper method for encode()
     *
     * @param array $assignments The array to which assignments are to be
     *   appended
     * @param mixed $object The data structure to be encoded; $object may be any
     *   value composed from strings using indexed arrays, associative arrays,
     *   and instances of stdClass
     * @param string $prefix An initial segment of the left-hand side of an
     *   assignment
     */
    private static function encodeImpl(&$assignments, $object, $prefix = null)
    {
        if (is_array($object) && Array_::isIndexed($object)) {
            foreach ($object as $n => $v)
                self::encodeImpl($assignments, $v, $prefix . '[' . $n . ']');
        } elseif (is_array($object) || is_object($object)) {
            foreach ($object as $n => $v) {
                $name = $prefix === null ? $n : $prefix . '[' . $n . ']';
                self::encodeImpl($assignments, $v, $name);
            }
        } elseif (is_scalar($object)) {
            $assignments[] = [$prefix, $object];
        }
    }

    /**
     * Returns an empty array or instance of stdClass, depending on the value of
     * the parameter $objectsAsArrays
     *
     * @param boolean $objectsAsArrays true to use associate arrays instead of
     *   instances, of stdClass when constructing the result
     * @return mixed
     */
    private static function newObject($objectsAsArrays)
    {
        return $objectsAsArrays ? [] : new stdClass;
    }
}
