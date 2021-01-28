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

use const CodeRage\Build\COMMAND_LINE;
use const CodeRage\Build\CONSOLE;
use const CodeRage\Build\ENVIRONMENT;
use const CodeRage\Build\ISSET_;
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
     *   construction
     * @param int $flags A bitwise OR of zero or more of the constants
     *   CodeRage\Build\XXX
     * @param mixed $value The value of the property under construction, if
     *   ($flags & CodeRage\Build\ISSET_) != 0, and null otherwise.
     * @param mixed $specifiedAt A file pathname or one of the constants
     *   CodeRage\Build\ENVIRONMENT, CodeRage\Build\COMMAND_LINE,
     *   CodeRage\Build\CONSOLE, or null.
     * @param mixed $setAt A file pathname or one of the constants
     *   CodeRage\Build\ENVIRONMENT, CodeRage\Build\COMMAND_LINE,
     *   CodeRage\Build\CONSOLE, or null.
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

        // Validate value
        if ($flags & ISSET_ && $value === null) {
            throw new
                Error([
                    'message' =>
                        "Invalid value for property '$name': expected null; " .
                        "found " . Error::formatValue($value)
                ]);
        }

        // Validate setAt
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
        $this->setAt = $setAt;
    }

    /**
     * Returns the fully-qualified name of this property.
     *
     * @return string
     */
    function name() { return $this->name; }

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
            return '[command-line]';
        case ENVIRONMENT:
            return '[environment]';
        case CONSOLE:
            return '[console]';
        default:
            throw new Error(['message' => "Unknown location: $location"]);
        }
    }
}
