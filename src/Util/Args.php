<?php

/**
 * Defines the class CodeRage\Util\Args
 *
 * File:        CodeRage/Util/Args.php
 * Date:        Thu Sep 17 02:04:09 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;
use CodeRage\Text;
use CodeRage\Util\Array_;

/**
 * Container for static methods used to validate and process function arguments
 */
final class Args {

    /**
     * @var string
     */
    public const MATCH_BOOLEAN = '/^(1|0|true|false)$/';

    /**
     * @var string
     */
    public const MATCH_INT = '/^-?(0|[1-9][0-9]*)$/';

    /**
     * @var string
     */
    public const MATCH_DATE =
        '/^(?<year>\d{4})(?<dsep>-?)(?<month>\d{2})(\k<dsep>)(?<day>\d{2})$/';

    /**
     * @var string
     */
    public const MATCH_DATETIME =
        '/^(?<year>\d{4})(?<dsep>-?)(?<month>\d{2})(\k<dsep>)(?<day>\d{2})
          T(?<hour>\d{2})(?<tsep>:?)(?<min>\d{2})((\k<tsep>)(?<sec>\d{2}))?
          (Z|(?<odir>[-+])(?<ohour>\d{2})(:?(?<omin>\d{2}))?)?$/x';

    /**
     * Throws an exception if the given value is not an instance of the named
     * type
     *
     * @param mixed $value The value
     * @param string $type A pipe-separated list of type names; acceptable
     *   values are "boolean", "int", "float", "number", "string", "ascii",
     *   "scalar", "array", "list", "map", "object", "callable", "regex",
     *   "date", "datetime", and any class or interface name; "array", "list",
     *   and "map" may be qualified by appending a type name in square brackets
     *   indicating the type of values supported by the collection, e.g.,
     *   "list[int]". Expressions involvingsquare brackets may not be nested.
     * @param string $label Descriptive text for use in an error message
     * @param boolean $nothrow true to return false instead of throwing an
     *   exception if $value is not valids
     * @return boolean true if $nothrow is false or $value is valid
     * @throws CodeRage\Error
     */
    public static function check($value, $type, $label, $nothrow = false)
    {
        static $matchType;
        if ($matchType === null) {
            $atom =
                '(boolean|int|float|number|string|ascii|scalar|array|list|map|object|callable|regex|date|datetime|
                      \\\?[_a-zA-Z][_a-zA-Z0-9]*(\\\[_a-zA-Z][_a-zA-Z0-9]*)*)
                   (\\|(boolean|int|float|number|string|ascii|scalar|array|list|map|object|callable|regex|date|datetime|
                      \\\?[_a-zA-Z][_a-zA-Z0-9]*(\\\[_a-zA-Z][_a-zA-Z0-9]*)*))*';
            $collection = "((array|list|map)\[$atom\])";
            $matchType =
                "/^(boolean|int|float|number|string|scalar|ascii|$collection|object|callable|regex|date|datetime|
                      \\\?[_a-zA-Z][_a-zA-Z0-9]*(\\\[_a-zA-Z][_a-zA-Z0-9]*)*)
                   (\\|(boolean|int|float|number|string|ascii|scalar|$collection|object|callable|regex|date|datetime|
                      \\\?[_a-zA-Z][_a-zA-Z0-9]*(\\\[_a-zA-Z][_a-zA-Z0-9]*)*))*$/x";

        }
        if (!is_string($type))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid type: expected string; found ' .
                        Error::formatValue($type)
                ]);
        if (!preg_match($matchType, $type))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid type list: $type"
                ]);
        if (!is_string($label))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid label: expected string; found ' .
                        Error::formatValue($label)
                ]);
        $valid = false;
        $types = explode('|', $type);

        // Collect allowed list and map item types; an array of types indicates
        // that if $value is a collection of the appropriate type, it must be
        // it must be homogenous with items of one of the given types; a null
        // value indicates that there are no constraints on the item type; if
        // both are null, any array is acceptable
        $listItemTypes = $mapItemTypes = [];
        foreach ($types as $i => $t) {
            if ($t == 'array') {
                $listItemTypes = $mapItemTypes = null;
                break;
            }
            if (strncmp($t, 'list', 4) == 0 && $listItemTypes !== null) {
                $len = strlen($t);
                if ($len == 4) {
                    $listItemTypes = null;
                    continue;
                }
                $listItemTypes[] = substr($t, 5, $len - 6);
            }
            if (strncmp($t, 'map', 3) == 0 && $mapItemTypes !== null) {
                $len = strlen($t);
                if ($len == 3) {
                    $mapItemTypes = null;
                    continue;
                }
                $mapItemTypes[] = substr($t, 4, $len - 5);
            }
        }

        // Handle non-collections
        foreach (explode('|', $type) as $t) {
            $item = null;
            if (($pos = strpos($t, '[')) !== false) {
                $item = substr($t, $pos +1, strlen($t) - $pos - 2);
                $t = substr($t, 0, $pos);
            }
            switch ($t) {
            case 'boolean':
                if (is_bool($value)) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'int':
                if (is_int($value)) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'float':
                if (is_float($value)) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'number':
                if (is_int($value) || is_float($value)) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'string':
                if (is_string($value)) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'ascii':
                if (is_string($value) && mb_check_encoding($value, 'ascii')) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'scalar':
                if (is_scalar($value)) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'array':
            case 'list':
            case 'map':
                break;
            case 'object':
                if (is_object($value)) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'callable':
                if (is_callable($value)) {
                    $valid = true;
                    break 2;
                }
                break;
            case 'regex':
                if (is_string($value)) {
                    $handler = new ErrorHandler;
                    $handler->_preg_match($value, "");
                    if (!$handler->errno()) {
                        $valid = true;
                        break 2;
                    }
                }
                break;
            case 'date':
                if (is_string($value)) {
                    $m = null;
                    if ( preg_match(Args::MATCH_DATE, $value, $m) &&
                         checkdate($m['month'], $m['day'], $m['year']) )
                    {
                        $valid = true;
                        break 2;
                    }
                }
                break;
            case 'datetime':
                if (is_string($value)) {
                    $m = null;
                    if ( preg_match(Args::MATCH_DATETIME, $value, $m) &&
                         checkdate($m['month'], $m['day'], $m['year'])
                          && $m['hour'] < 24
                          && $m['min'] < 60
                          && (!isset($m['sec']) || $m['sec'] < 60)
                          && (!isset($m['ohour']) || $m['ohour'] < 24)
                          && (!isset($m['omin']) || $m['omin'] < 60)
                         )
                    {
                        $valid = true;
                        break 2;
                    }
                }
                break;
            default:
                if ($value instanceof $t) {
                    $valid = true;
                    break 2;
                }
                break;
            }
        }

        // Handle collections
        $invalidItemTypes = [];
        if (!$valid && is_array($value)) {
            for (;;) {
                if ($listItemTypes === null && $mapItemTypes === null) {
                    $valid = true;
                    break;
                }
                $isIndexed = Array_::isIndexed($value);
                if ( $listItemTypes === null && $isIndexed ||
                     $mapItemTypes === null && (empty($value) || !$isIndexed) )
                {
                    $valid = true;
                    break;
                }
                if ($listItemTypes !== null && $isIndexed) {
                    $goodTypes = [];
                    foreach ($listItemTypes as $t)
                        $goodTypes[$t] = true;
                    foreach ($value as $v) {
                        foreach ($listItemTypes as $t) {
                            if ($goodTypes[$t] && !self::check($v, $t, '', true)) {
                                $goodTypes[$t] = false;
                                $invalidItemTypes[Error::formatType($v)] = 1;
                            }
                        }
                    }
                    foreach ($goodTypes as $v) {
                        if ($v) {
                            $valid = true;
                            break;
                        }
                    }
                }
                if ($mapItemTypes !== null && (empty($value) || !$isIndexed)) {
                    $goodTypes = [];
                    foreach ($mapItemTypes as $t)
                        $goodTypes[$t] = true;
                    foreach ($value as $v) {
                        foreach ($mapItemTypes as $t) {
                            if ($goodTypes[$t] && !self::check($v, $t, '', true)) {
                                $goodTypes[$t] = false;
                                $invalidItemTypes[Error::formatType($v)] = 1;
                            }
                        }
                    }
                    foreach ($goodTypes as $v) {
                        if ($v) {
                            $valid = true;
                            break;
                        }
                    }
                }
                break;
            }
        }

        if (!$valid && !$nothrow) {

            // Throw an exception
            $found = is_scalar($value) ?
                $value :
                Error::formatValue($value);
            if (is_array($value)) {
                $isIndexed = Array_::isIndexed($value);
                if ( (!$isIndexed || empty($value)) &&
                     ($listItemTypes === null || count($listItemTypes) > 0) )
                {
                    $found = 'map';
                } elseif ( $isIndexed &&
                           ($mapItemTypes === null || count($mapItemTypes) > 0) )
                {
                    $found = 'list';
                } else {
                    $found = $isIndexed ? 'list' : 'map';
                    if (!empty($invalidItemTypes)) {
                        $found .=
                            ' containing items of type ' .
                            Text::formatList(array_keys($invalidItemTypes), 'and', "'");
                    }
                }
            }
            $types =
                Array_::map(function($t)
                {
                    if (strncmp($t, 'list[', 5) == 0) {
                        return "list with item type '" .
                               substr($t, 5, strlen($t) - 6) . "'";
                    } elseif (strncmp($t, 'map[', 4) == 0) {
                        return "map with item type '" .
                               substr($t, 4, strlen($t) - 5) . "'";
                    } else {
                        return $t;
                    }
                }, $types);
            $expected = Text::formatList($types, 'or');
            $message = "Invalid $label";
            //if (is_scalar($value))
            //    $message .= ": $value";
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => $message,
                    'details' =>
                        "Invalid $label: expected $expected; found $found"
                ]);
        }

        return $valid;
    }

    /**
     * Validates and applies defaults for the named value.
     *
     * @param mixed $options A collection of named values, as an array or an
     *   instance of ArrayAccess
     * @param string $name The name of the value to be validated
     * @param string $type A pipe-separated list of type names; acceptable values
     *   are "boolean", "int", "float", "number", "string", "ascii", "scalar",
     *   "array", "list", "map", "object", "callable", "regex", "date", "datetime",
     *   and and class or interface name
     * @param array $params An associative array with keys among
     *     label - Descriptive text for use in an error message; defaults
     *       to $name
     *     required - true to cause an exception to be thrown if the value is not
     *       present or is null; defaults to false
     *     default - The default value, if any
     *     unset - true to remove the key from the array
     *   For backward compatibility, the arguments list $label, $required, $default
     *   may be passed in place of $params
     * @return mixed The value of the option, if any
     * @throws CodeRage\Error
     */
    public static function checkKey(
        &$options, $name, $type, $params = null, $deprecated1 = false,
        $deprecated2 = null)
    {
        if ($params === null || !is_array($params) || func_num_args() >= 5) {

            // Backward compatibility mode
            $params =
                [
                    'label' => $params,
                    'required' => $deprecated1,
                ];
            if (func_num_args() == 6)
                $params['default'] = $deprecated2;
        }

        // Check argument types
        if (!is_array($options) && !$options instanceof \ArrayAccess)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid options collection: expected array or instance ' .
                        'of ArrayAccess; found ' . Error::formatValue($options)
                ]);
        if (!is_string($name))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid option name: expected string; found ' .
                        Error::formatValue($name)
                ]);
        if (!is_string($type))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid type: expected string; found ' .
                        Error::formatValue($type)
                ]);
        if (!is_array($params))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Expected array of parameters: found ' .
                        Error::formatValue($params)
                ]);
       if (!empty($params) && Array_::isIndexed($params))
           throw new
               Error([
                   'status' => 'INVALID_PARAMETER',
                   'details' =>
                       'Expected associative array of parameters: found ' .
                       'indexed array'
               ]);

        // Apply default values
        if (!isset($params['label']))
            $params['label'] = $name;
        if (!isset($params['required']))
            $params['required'] = false;

        // Check parameter types
        if (!is_string($params['label']))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid label: expected string; found ' .
                        Error::formatValue($params['label'])
                ]);
        if (!is_bool($params['required']))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        'Invalid required flag: expected boolean; found ' .
                        Error::formatValue($params['required'])
                ]);

        // Handle missing option
        if (!isset($options[$name])) {
            if ($params['required']) {
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' => "Missing {$params['label']}"
                    ]);
            } elseif (!array_key_exists('default', $params)) {
                return null;
            } else {
                $options[$name] = $params['default'];
            }
        }

        // Validate
        if (isset($options[$name]))
            self::check($options[$name], $type, $params['label']);

        // Remove option, if applicable
        $result = $options[$name];
        if ($params['unset'] ?? false) {
            unset($options[$name]);
        }

        return $result;
    }

    /**
     * Validates and processes the named option, which must be a boolean or the
     * string representation of a boolean, coercing it to a boolean if it is a
     * string
     *
     * @param mixed $options A collection of named values, as an array or an
     *   instance of ArrayAccess
     * @param string $name The name of the value to be validated
     * @param array $params An associative array with keys among
     *     label - Descriptive text for use in an error message; defaults
     *       to $name
     *     required - true to cause an exception to be thrown if the value is not
     *       present or is null; defaults to false
     *     default - The default value, if any
     * @return mixed The value of the option, if any
     * @throws CodeRage\Error
     */
    public static function checkBooleanKey(&$options, $name, array $params = [])
    {
        $value = self::checkKey($options, $name, 'boolean|int|string', $params);
        if (is_int($value) || is_string($value)) {
            $value = (string) $value;
            if (!preg_match(self::MATCH_BOOLEAN, $value)) {
                $label = isset($params['label']) ? $params['label'] : $name;
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid $label: expected boolean; found $value"
                    ]);
            }
            $options[$name] = $value = $value == '1' || $value == 'true';
        }
        return $value;
    }

    /**
     * Validates and processes the named option, which must be an integer or the
     * string representation of an integer, coercing it to an integer if it is a
     * string
     *
     * @param mixed $options A collection of named values, as an array or an
     *   instance of ArrayAccess
     * @param string $name The name of the value to be validated
     * @param array $params An associative array with keys among
     *     label - Descriptive text for use in an error message; defaults
     *       to $name
     *     required - true to cause an exception to be thrown if the value is
     *       not present or is null; defaults to false
     *     default - The default value, if any
     * @return mixed The value of the option, if any
     * @throws CodeRage\Error
     */
    public static function checkIntKey(&$options, $name, array $params = [])
    {
        $value = self::checkKey($options, $name, 'int|string', $params);
        if (is_string($value)) {
            if (!preg_match(self::MATCH_INT, $value)) {
                $label = isset($params['label']) ? $params['label'] : $name;
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid $label: expected int; found $value"
                    ]);
            }
            $options[$name] = $value = (int) $value;
        }
        return $value;
    }

    /**
     * Validates and processes the named option, which must be a number or the
     * string representation of a number, coercing it to a float if it is a
     * string
     *
     * @param mixed $options A collection of named values, as an array or an
     *   instance of ArrayAccess
     * @param string $name The name of the value to be validated
     * @param array $params An associative array with keys among
     *     label - Descriptive text for use in an error message; defaults
     *       to $name
     *     required - true to cause an exception to be thrown if the value is
     *       not present or is null; defaults to false
     *     default - The default value, if any
     * @return mixed The value of the option, if any
     * @throws CodeRage\Error
     */
    public static function checkNumericKey(&$options, $name, array $params = [])
    {
        $value = self::checkKey($options, $name, 'number|string', $params);
        if (is_string($value)) {
            if (!is_numeric($value)) {
                $label = isset($params['label']) ? $params['label'] : $name;
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid $label: expected number; found $value"
                    ]);
            }
            $options[$name] = $value = (float) $value;
        }
        return $value;
    }

    /**
     * Returns the unique element of the given list of keys that is assigned a
     * non-null value by the given collection of named values
     *
     * @param mixed $options A collection of named values, as an array or an
     *   instance of ArrayAccess
     * @param string $names A list of strings
     * @return string
     * @throws CodeRage\Error unless exactly one of the given list of keys is
     *   present in $options
     */
    public static function uniqueKey(array $options, array $names)
    {
        $count = count($names);
        if ($count < 2)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' =>
                        "Expected two or more option names; found $count"
                ]);
        $found = [];
        foreach ($names as $name) {
            if (isset($options[$name])) {
                $found[] = $name;
                if (count($found) == 2)
                    break;
            }
        }
        switch (count($found)) {
        case 0:
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' =>
                        'Missing ' . Text::formatList($names, 'or', "'")
                ]);
        case 1:
            return $found[0];
        case 2:
        default:
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        "The options '{$found[0]}' and '{$found[1]}' are " .
                        "incompatible"
                ]);
        }
    }
}
