<?php

/**
 * Defines the class CodeRage\Build\Config\Property.
 *
 * File:        CodeRage/Build/Config/Property.php
 * Date:        Wed Jan 23 11:43:42 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config;

use const CodeRage\Build\BOOLEAN;
use const CodeRage\Build\COMMAND_LINE;
use const CodeRage\Build\CONSOLE;
use const CodeRage\Build\ENVIRONMENT;
use const CodeRage\Build\FLOAT;
use const CodeRage\Build\INT;
use const CodeRage\Build\ISSET_;
use const CodeRage\Build\LIST_;
use const CodeRage\Build\STRING;
use const CodeRage\Build\TYPE_MASK;
use CodeRage\Error;

/**
 * Represents a property value plus metadata.
 */
class Property {

    /**
     * The fully-qualified name of this property.
     *
     * @var string
     */
    private $name;

    /**
     * A bitwise OR of zero or more of the constants
     * CodeRage\Build\XXX.
     *
     * @var int
     */
    private $flags;

    /**
     * The value of this property, if
     * ($flags & CodeRage\Build\ISSET_) != 0, and null otherwise.
     *
     * @var mixed
     */
    private $value;

    /**
     * A file pathname or one of the constants CodeRage\Build\ENVIRONMENT,
     * CodeRage\Build\COMMAND_LINE, CodeRage\Build\CONSOLE, or null.
     *
     * @var mixed
     */
    private $specifiedAt;

    /**
     * A file pathname, one of the constants CodeRage\Build\ENVIRONMENT,
     * CodeRage\Build\COMMAND_LINE, CodeRage\Build\CONSOLE, or null.
     *
     * @var mixed
     */
    private $setAt;

    /**
     * Constructs a CodeRage\Build\Config\Property.
     *
     * @param string $name The fully-qualified name of the property under
     * construction.
     * @param int $flags A bitwise OR of zero or more of the constants
     * CodeRage\Build\XXX.
     * @param mixed $value The value of the property under construction, if
     * ($flags & CodeRage\Build\ISSET_) != 0, and null otherwise.
     * @param mixed $specifiedAt A file pathname or one of the constants
     * CodeRage\Build\ENVIRONMENT, CodeRage\Build\COMMAND_LINE, CodeRage\Build\CONSOLE, or null.
     * @param mixed $setAt A file pathname or one of the constants
     * CodeRage\Build\ENVIRONMENT, CodeRage\Build\COMMAND_LINE, CodeRage\Build\CONSOLE, or null.
     */
    function __construct($name, $flags, $value, $specifiedAt, $setAt)
    {
        // Validate name
        if (!is_string($name))
            throw new
                Error(['message' =>
                    'Invalid property name: expected string; found ' .
                    Error::formatValue($name)
                ]);

        // Validate flags
        $type = ($flags & TYPE_MASK);
        if (pow(2, floor(log($type, 2))) != $type)
            throw new Error(['message' => "Invalid flags: $flags"]);

        // Validate value
        if ($flags & ISSET_) {
            if ($flags & LIST_) {
                if (!is_array($value))
                    throw new
                        Error(['message' =>
                            "Invalid value for property '$name': expected " .
                            "array; found " . Error::formatValue($value)
                        ]);
                foreach ($value as $v)
                    if (!$this->checkValue($v, $type))
                        throw new
                            Error(['message' =>
                                "Invalid value for property '$name': " .
                                " expected " . $this->translateType($type) .
                                "; found " . Error::formatValue($v)
                            ]);
            } elseif (!$this->checkValue($value, $type)) {
                throw new
                    Error(['message' =>
                        "Invalid value for property '$name': expected " .
                        $this->translateType($type) . "; found " .
                        Error::formatValue($value)
                    ]);
            }
        } elseif ($value !== null) {
            throw new
                Error(['message' =>
                    "Invalid value for property '$name': expected null; " .
                    "found " . Error::formatValue($value)
                ]);
        }

        // Validate specifiedAt and setAt
        if ( $specifiedAt !== null &&
             !is_string($specifiedAt) &&
             $specifiedAt !== COMMAND_LINE &&
             $specifiedAt !== ENVIRONMENT &&
             $specifiedAt !== CONSOLE )
        {
            throw new
                Error(['message' =>
                    "Invalid value for 'specifiedAt': " .
                    Error::formatValue($specifiedAt)
                ]);
        }
        if ($setAt !== null) {
            if ( !is_string($setAt) &&
                 $setAt !== COMMAND_LINE &&
                 $setAt !== ENVIRONMENT &&
                 $setAt !== CONSOLE )
            {
                throw new
                    Error(['message' =>
                        "Invalid value for 'setAt': " .
                        Error::formatValue($setAt)
                    ]);
            }
            if (($flags & ISSET_) == 0)
                throw new
                    Error(['message' =>
                        "Invalid value for 'setAt': expected null; found $setAt"
                    ]);
        }

        // Set properties
        $this->name = $name;
        $this->flags = $flags;
        $this->value = $value;
        $this->specifiedAt = $specifiedAt;
        $this->setAt = $setAt;
    }

    /**
     * Returns the fully-qualified name of this property.
     *
     * @return string
     */
    function name() { return $this->name; }

    /**
     * Returns a bitwise OR of:
     *
     * <ul>
     * <li>At most one of the constants CodeRage\Build\XXX, where
     * XXX is BOOLEAN, INT, FLOAT, or STRING, and</li>
     * <li>The constant CodeRage\Build\LIST_, or zero.
     * </ul>
     *
     * @return int
     */
    function type()
    {
        return ($this->flags & TYPE_MASK);
    }

    /**
     * Returns true if this property consists of a list of values.
     *
     * @return bool
     */
    function isList() { return ($this->flags & LIST_) != 0; }

    /**
     * Returns true if this property has been assigned a value, possibly null.
     *
     * @return boolean
     */
    function isSet_()
    {
        return ($this->flags & ISSET_) != 0;
    }

    /**
     * Returns the value of this property, if it is set, and null otherwise.
     *
     * @return mixed
     */
    function value() { return $this->value; }

    /**
     * Returns the location where this property was specified. If this
     * property's features were specified in several different locations, the
     * nearest location is returned.
     *
     * @return mixed A file pathname or one of the constants
     * CodeRage\Build\ENVIRONMENT, CodeRage\Build\COMMAND_LINE, CodeRage\Build\CONSOLE, or null.
     */
    function specifiedAt() { return $this->specifiedAt; }

    /**
     * Returns the location where this property's value was defined.
     *
     * @return mixed A file pathname or one of the constants
     * CodeRage\Build\ENVIRONMENT, CodeRage\Build\COMMAND_LINE, CodeRage\Build\CONSOLE, or null.
     */
    function setAt() { return $this->setAt; }

    /**
     * Implements the method isSet().
     *
     * @return boolean
     * @throws Exception
     */
    function __call($method, $args)
    {
        switch ($method) {
        case 'isSet':
            return $this->isSet_();
        default:
            throw new
                Error(['message' =>
                    "No such method: CodeRage\\Build\\Config\\Property::$method"
                ]);
        }
    }

    /**
     * Translates the given type constant into a human readable string.
     *
     * @param int $type One of the constants CodeRage\Build\XXX, where
     * XXX is BOOLEAN, INT, FLOAT, or STRING.
     * @return string
     */
    static function translateType($type)
    {
        switch ($type) {
        case BOOLEAN:
            return 'boolean';
        case INT:
            return 'int';
        case FLOAT:
            return 'float';
        case STRING:
            return 'string';
        default:
            throw new Error(['message' => "Unknown type: $type"]);
        }
    }

    /**
     * Translates the given location into a human readable string.
     *
     * @param mixed $location A file pathname or one of the constants
     * CodeRage\Build\XXX.
     * @return string
     */
    static function translateLocation($location)
    {
        if (is_string($location))
            return $location;
        switch ($location) {
        case COMMAND_LINE:
            return '<command-line>';
        case ENVIRONMENT:
            return '<environment>';
        case CONSOLE:
            return '<console>';
        default:
            throw new Error(['message' => "Unknown location: $location"]);
        }
    }

    /**
     * Returns true if the given value conforms to the specified type.
     *
     * @param mixed $value
     * @param int $type One of the constants CodeRage\Build\XXX, where
     * XXX is BOOLEAN, INT, FLOAT, or STRING, or zero.
     * @return boolean
     */
    private static function checkValue($value, $type)
    {
        switch ($type) {
        case BOOLEAN:
            return is_bool($value);
        case INT:
            return is_int($value);
        case FLOAT:
            return is_int($value) || is_float($value) && is_finite($value);
        case STRING:
        case 0:
            return is_string($value);
        default:
            throw new Error(['message' => "Unknown type: $type"]);
        }
    }
}
