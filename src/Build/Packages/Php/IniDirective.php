<?php

/**
 * Defines the class CodeRage\Build\Packages\Php\IniDirective
 * 
 * File:        CodeRage/Build/Packages/Php/IniDirective.php
 * Date:        Wed Feb 06 14:57:45 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Packages\Php;

use CodeRage\Error;
use CodeRage\Util\ErrorHandler;
use function CodeRage\Util\printScalar;

/**
 * @ignore
 */
require_once('CodeRage/File/checkReadable.php');
require_once('CodeRage/Util/printScalar.php');

/**
 * Represents the specification of a php.ini directive.
 */
class IniDirective {

    /**
     * The fully qualified name of this directive.
     *
     * @var string
     */
    private $name;

    /**
     * One of the constants CodeRage\Build\Packages\Php\Ini::XXX, where XXX is
     * BOOLEAN_, INT_, STRING_, PATH, or PATHLIST.
     *
     * @var int
     */
    private $type;

    /**
     * An associative array mapping one or more constants of the form
     * CodeRage\Build\Packages\Php\Ini::XXX to strings.
     *
     * @var array
     */
    private $specialValues;

    /**
     * The value of this directive.
     *
     * @var mixed
     */
    private $value;

    /**
     * true if the value of this directive has been modified using setValue().
     *
     * @var boolean
     */
    private $modified = false;

    /**
     * Constructs a CodeRage\Build\Packages\Php\IniDirective.
     *
     * @param string $name The fully qualified name of the directive under
     * construction
     * @param int $type One of the constants CodeRage\Build\Packages\Php\Ini::XXX,
     * where XXX is BOOLEAN_, INT_, STRING_, PATH, or PATHLIST.
     * @param array $specialValues An associative array mapping one or more
     * constants of the form CodeRage\Build\Packages\Php\Ini::XXX to strings, or null
     * if the directive under construction does not have any special values.
     */
    protected function __construct($name, $type, $specialValues = null)
    {
        switch ($type) {
        case Ini::BOOLEAN_:
        case Ini::INT_:
        case Ini::STRING_:
        case Ini::PATH:
        case Ini::PATHLIST:
            break;
        default:
            throw new Error(['message' => "Unknown type: $type"]);
        }
        $this->name = $name;
        $this->type = $type;
        $this->specialValues = $specialValues;
    }

    /**
     * Returns the fully qualified name of this directive.
     *
     * @return string
     */
    function name()
    {
        return $this->name;
    }

    /**
     * Returns one of the constants CodeRage\Build\Packages\Php\Ini::XXX, where XXX is
     * BOOLEAN_, INT_, STRING_, PATH, or PATHLIST.
     *
     * @return int
     */
    function type()
    {
        return $this->type;
    }

    /**
     * Returns associative array mapping one or more constants of the form
     * CodeRage\Build\Packages\Php\Ini::XXX to strings, or null if this directive does
     * not have any special values.
     *
     * @return array
     */
    function specialValues()
    {
        return $this->specialValues;
    }

    /**
     * Returns the value of this directive.
     *
     * @return mixed
     */
    function value()
    {
        return $this->value;
    }

    /**
     * Sets the value of this directive.
     *
     * @param $value mixed
     * @throws CodeRage\Error
     */
    function setValue($value)
    {
        $this->setValueImpl($value);
        $this->modified = true;
    }

    /**
     * Returns true if the value of this directive has been modified using
     * setValue().
     *
     * @return boolean
     */
    function modified()
    {
        return $this->modified;
    }

    /**
     * Translates the given string into a value of the form used by
     * CodeRage\Build\Packages\Manager::getConfigurationProperty() and
     * CodeRage\Build\Packages\Manager::setConfigurationProperty().
     *
     * @param string $value A string suitable for inclusion in a php.ini file as
     * the value of a php.ini directive.
     * @return string
     */
    function fromString($value)
    {
        $value = trim($value);
        if ($value == '' || strcasecmp($value, 'none') == 0)
            return null;
        switch ($this->type) {
        case Ini::BOOLEAN_:
            if (!preg_match('/^on|off|true|false|yes|no|1|0$/i', $value))
                throw new
                    Error(['message' =>
                        "Invalid argument to " .
                        "CodeRage\Build\Packages\Php\IniDirective::fromString() " .
                        "for directive '$this->name': expected one of 'On', " .
                        "'Off', 'True', 'False', 'Yes', 'No', '1', or '0'; " .
                        "found $value"
                    ]);
            switch (strtolower($value)) {
            case 'on':
            case 'true':
            case 'yes':
                return true;
            default:
                return false;
            }
        case Ini::INT_:
            if (!is_numeric($value) || floatval($value) != intval($value))
                throw new
                    Error(['message' =>
                        "Invalid argument to " .
                        "CodeRage\Build\Packages\Php\IniDirective::fromString() " .
                        "for directive '$this->name': expected int; found " .
                        $value
                    ]);
            return intval($value);
        case Ini::STRING_:
        case Ini::PATH:
            return $value;
        case Ini::PATHLIST:
            return explode(PATH_SEPARATOR, $value);
        default:
            return null; // can't happen
        }
    }

    /**
     * Translates the given value into a string suitable for inclusion in a
     * php.ini file as the value of a php.ini directive.
     *
     * @param mixed $value a value of the form used by
     * CodeRage\Build\Packages\Manager::getConfigurationProperty() and
     * CodeRage\Build\Packages\Manager::setConfigurationProperty().
     * @return string
     */
    function toString($value)
    {
        if ($value === null)
            return 'none';
        switch ($this->type) {
        case Ini::BOOLEAN_:
            if (!is_bool($value))
                throw new
                    Error(['message' =>
                        'Invalid argument to ' .
                        'CodeRage\Build\Packages\Php\IniDirective::toString(); ' .
                        'expected boolean; found ' .
                        printScalar($value)
                    ]);
            return $value ? 'On' : 'Off';
        case Ini::INT_:
            if (!is_int($value))
                throw new
                    Error(['message' =>
                        'Invalid argument to ' .
                        'CodeRage\Build\Packages\Php\IniDirective::toString(); ' .
                        'expected int; found ' .
                        printScalar($value)
                    ]);
            return strval($value);
        case Ini::PATH:
        case Ini::STRING_:
            if (!is_string($value))
                throw new
                    Error(['message' =>
                        'Invalid argument to ' .
                        'CodeRage\Build\Packages\Php\IniDirective::toString(); ' .
                        'expected string; found ' .
                        printScalar($value)
                    ]);
            return strpos($value, ';') !== false ||
                   preg_match('/\n(?!$)/', $value) ?
                "\"$value\"" :
                $value;
        case Ini::PATHLIST:
            if (!is_array($value))
                throw new
                    Error(['message' =>
                        'Invalid argument to ' .
                        'CodeRage\Build\Packages\Php\IniDirective::toString(); ' .
                        'expected array; found ' .
                        printScalar($value)
                    ]);
            return '"' . join(PATH_SEPARATOR, $value) . '"';
        default:
            return null; // can't happen
        }
    }

    /**
     * Returns a directive with the given name, or null if the named directive
     * is not supported.
     *
     * @param string $name
     */
    static function create($name)
    {
        switch ($name) {
        case 'include_path':
            return new
                IniDirective(
                    'include_path',
                    Ini::PATHLIST
                );
        default:
            return new
                IniDirective(
                    $name,
                    Ini::STRING_
                );
        }
    }

    /**
     * Returns a string representation of this directive, suitable for includion
     * in an .ini file.
     *
     * @return string
     */
    function __toString()
    {
        return "$this->name = " . $this->toString($this->value) . "\n";
    }

    /**
     * Sets the value of this directive.
     *
     * @param $value mixed
     * @throws CodeRage\Error
     */
    function setValueImpl($value)
    {
        if ($value !== null) {
            switch ($this->type) {
            case Ini::BOOLEAN_:
                if (!is_bool($value))
                    throw new
                        Error(['message' =>
                            "Invalid argument to " .
                            "CodeRage\Build\Packages\Php\IniDirective::setValue() " .
                            "for directive '$this->name'; expected boolean; " .
                            "found " . printScalar($value)
                        ]);
                break;
            case Ini::INT_:
                if (!is_int($value))
                    throw new
                        Error(['message' =>
                            "Invalid argument to " .
                            "CodeRage\Build\Packages\Php\IniDirective::setValue() " .
                            "for directive '$this->name'; expected int; " .
                            "found " . printScalar($value)
                        ]);
                break;
            case Ini::PATH:
            case Ini::STRING_:
                if (!is_string($value))
                    throw new
                        Error(['message' =>
                            "Invalid argument to " .
                            "CodeRage\Build\Packages\Php\IniDirective::setValue() " .
                            "for directive '$this->name'; expected string; " .
                            "found " . printScalar($value)
                        ]);
                break;
            case Ini::PATHLIST:
                if (!is_array($value))
                    throw new
                        Error(['message' =>
                            "Invalid argument to " .
                            "CodeRage\Build\Packages\Php\IniDirective::setValue() " .
                            "for directive '$this->name'; expected array; " .
                            "found " . printScalar($value)
                        ]);
                break;
            default:
                break; // can't happen
            }
        }
        $this->value = $value;
    }
}
