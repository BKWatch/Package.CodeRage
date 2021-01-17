<?php

/**
 * Defines the class CodeRage\Util\CommandLineOption
 * 
 * File:        CodeRage/Util/CommandLineOption.php
 * Date:        Sat Nov 10 17:18:34 MST 2007
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Represent a command-line option
 *
 */
final class CommandLineOption {

    /**
     * Constructs a CodeRage\Util\CommandLineOption.
     *
     * @param array $options The options array; supports the following options:
     *     longForm - The form of the option as it can be passed preceded by a
     *       double hyphen (optional)
     *     shortForm - The form of the option as it can be passed preceded by a
     *       single hyphen (optional)
     *     required - true if the option is required (optional)
     *     type - One of 'switch', 'boolean', 'int', 'float', or 'string';
     *       defaults to 'string'
     *     default - The default value, if any, of the option (optional)
     *     label - A brief descriptive name of the option, for use in error
     *       messages (optional)
     *     placeholder - The string used as the sample value of the option
     *       under construction when usage information is displayed; one way to
     *       set the placeholder property is to surround its occurrence in
     *       the option description with doubled angle brackets, e.g. <<val>>
     *       (optional)
     *     description: A description of the option; an embedded value of the
     *       form <<val>> is treated as a sample value of the option (optional)
     *     multiple - true if multiple occurrences of the option are permitted;
     *       defaults to false
     *     valueOptional - true the value of the option may be omitted
     *       (optional)
     *     action - A callback taking an instance of
     *       CodeRage\Util\CommandLine as an argument, used to execute a switch
     *       option (optional)
     *   At least one of longForm or shortForm must be provided.
     */
    function __construct(array $options)
    {
        static $names =
            [
                'longForm' => 1,
                'shortForm' => 1,
                'required' => true,
                'type' => 1,
                'default' => 1,
                'label' => 1,
                'placeholder' => 1,
                'description' => 1,
                'multiple' => 1,
                'valueOptional' => 1,
                'action' => 1
            ];
        foreach ($options as $n => $v)
            if (!isset($names[$n]))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Illegal option: $n"
                    ]);
        Args::checkKey($options, 'longForm', 'string');
        Args::checkKey($options, 'shortForm', 'string');
        Args::checkKey($options, 'required', 'boolean', null, false, false);
        Args::checkKey($options, 'type', 'string', null, false, 'string');
        Args::checkKey($options, 'default', 'scalar');
        Args::checkKey($options, 'label', 'string');
        Args::checkKey($options, 'placeholder', 'string');
        Args::checkKey($options, 'description', 'string');
        if (isset($options['multiple'])) {
            $multiple = $options['multiple'];
            if ( $multiple !== true && $multiple !== false &&
                 $multiple !== 1 && $multiple !== 0 )
            {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Invalid 'multiple' flag: $multiple"
                    ]);
            }
            $options['multiple'] = (boolean) $options['multiple'];
        }
        Args::checkKey($options, 'multiple', 'boolean', null, false, false);
        Args::checkKey($options, 'valueOptional', 'boolean', null, false, false);
        Args::checkKey($options, 'action', 'callable');
        if (!isset($options['longForm']) && !isset($options['shortForm']))
            throw new
                Error([
                     'status' => 'MISSING_PARAMETER',
                      'message' => 'Missing longForm or shortForm'
                ]);
        if (isset($options['default']) && $options['multiple'])
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        'Default value not permitted for option admitting ' .
                        'multiple values'
                ]);
        if (!isset($options['default']))
            $options['default'] =
                $options['type'] == 'switch' ||
                $options['type'] == 'boolean' ?
                    false :
                    null;
        if ( $options['valueOptional'] &&
             ( $options['type'] == 'switch' ||
               $options['type'] == 'boolean' ||
               $options['multiple']) )
        {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        'Boolean options or options admitting multiple ' .
                        'values may not be specified as value-optional'
                ]);
        }
        if ( isset($options['description']) &&
             !isset($options['placeholder']) )
        {
            if (preg_match('/<<(.+)>>/', $options['description'], $match)) {
                $options['description'] =
                    preg_replace(
                        '/<<(.+)>>/',
                        '$1',
                        $options['description']
                    );
                $options['placeholder'] = $match[1];
            }
        }
        if ( isset($options['action']) &&
             $options['type'] != 'switch' &&
             $options['type'] != 'boolean' )
        {
            throw new
                Error([
                    'status' => 'INCONSISTENT_PARAMETERS',
                    'message' =>
                        'An action may be specified only for an option of ' .
                        'type switch'
                ]);
        }
        if (isset($options['longForm']))
            $this->setLongForm($options['longForm']);
        if (isset($options['shortForm']))
            $this->setShortForm($options['shortForm']);
        $this->required = $options['required'];
        $this->setType($options['type']);
        $this->setDefault($options['default']);
        if ($options['default'] !== null)
            $this->setValue($options['default'], false);
        $this->label = isset($options['label']) ?
            $options['label'] :
            null;
        $this->placeholder = isset($options['placeholder']) ?
            $options['placeholder'] :
            null;
        $this->description = isset($options['description']) ?
            $options['description'] :
            null;
        $this->multiple = $options['multiple'];
        $this->valueOptional = $options['valueOptional'];
        $this->action = isset($options['action']) ?
            $options['action'] :
            null;
    }

    /**
     * Returns the long form, if any, of this option
     *
     * @return string
     */
    public function longForm()
    {
        return $this->longForm;
    }

    /**
     * Returns the short form, if any, of this option
     *
     * @return string
     */
    public function shortForm()
    {
        return $this->shortForm;
    }

    /**
     * Returns true if this option is required
     *
     * @return boolean
     */
    public function required()
    {
        return $this->required;
    }

    /**
     * Returns the type of this option, represented by of the strings 'switch',
     * 'boolean', 'int', 'float', or 'string'
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns the default value, if any, of this option
     *
     * @return mixed
     */
    public function default_()
    {
        return $this->default;
    }

    /**
     * Returns a brief descriptive name of this option, for use in error
     * messages
     *
     * @return string
     */
    public function label()
    {
        return $this->label;
    }

    /**
     * The string used by as the sample value of this when usage information is
     * displayed
     *
     * @return array
     */
    public function placeholder()
    {
        return $this->placeholder;
    }

    /**
     * Returns the description ofthis option
     *
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * Returns true if multiple occurrences of this option are permitted
     *
     * @return boolean
     */
    public function multiple()
    {
        return $this->multiple;
    }

    /**
     * Returns true if the value of this option may be omitted
     *
     * @return boolean
     */
    public function valueOptional()
    {
        return $this->valueOptional;
    }

    /**
     * Returns the callback used to execute this option, if any
     *
     * @return callable A callback taking an instance of
     *   CodeRage\Util\CommandLine as an argument, used to execute a switch
     *   option
     */
    public function action()
    {
        return $this->action;
    }

    /**
     * Returns true if this option has been set
     *
     * @return boolean
     */
    public function hasValue()
    {
        return $this->value !== null;
    }

    /**
     * Returns true if this option has a value that was explicitly specified
     *
     * @return boolean
     */
    public function hasExplicitValue()
    {
        return $this->explicit;
    }

    /**
     * Returns the value, or list of values, of this option
     *
     * @param boolean $multiple true if a list of values, representing the
     *   values specified with multiple occurrences of this given option, should
     *   be returned.
     * @return mixed
     */
    public function value($multiple = false)
    {
        return $this->value === null ?
            ( $this->multiple && $multiple ? [] : null ) :
            ( $multiple ?
                  $this->value :
                  ( $this->multiple ?
                        $this->value[0] :
                        $this->value ) );
    }

    /**
     * Sets the value of this option.
     *
     * @param mixed $value
     * @param boolean $explicit true if the specified value should be regarded
     *   as having been explicitly set on the command line
     */
    public function setValue($value, $explicit = true)
    {
        if ($value !== null) {
            if ($this->multiple != is_array($value)) {
                $value = $this->multiple ?
                    '' :
                    ( is_bool($value) ?
                          ($value ? " 'true'" : " 'false'") :
                          " '$value'" );
                $expected = $this->multiple ? 'an array' : 'a scalar';
                $found = $this->multiple ? 'a scalar' : 'an array';
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "Invalid value$value for option $this; expected " .
                            "$expected; found $found"
                    ]);
            }
            if (is_array($value)) {
                foreach ($value as $v)
                    $this->testDataType($v);
            } else {
                $this->testDataType($value);
            }
        }
        $this->value = $value;
        $this->explicit = $explicit;
    }

    /**
     * Returns a string for storing this option in an array.
     *
     * @return string
     */
    public function key()
    {
        return $this->longForm ? $this->longForm : $this->shortForm;
    }

    public function __call($method, $args)
    {
        if ($method == 'default')
            return $this->default;
        throw new
            Error([
                'status' => 'UNSUPPORTED_OPERATION',
                'message' =>
                    "No such method: CodeRage\\Util\\CommandLineOption::$method"
            ]);
    }

    public function __toString()
    {
        return $this->longForm ? "--$this->longForm" : "-$this->shortForm";
    }

    /**
     * Sets the long form, if any, of this option
     *
     * @param string $longForm
     */
    private function setLongForm($longForm)
    {
        if ( !is_string($longForm) ||
             strlen($longForm) < 2 ||
             $longForm[0] == '-')
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Illegal long form: $longForm"
                ]);
        }
        $this->longForm = $longForm;
    }

    /**
     * Sets the short form, if any, of this option
     *
     * @param string $shortForm
     */
    private function setShortForm($shortForm)
    {
        if ( !is_string($shortForm) ||
             strlen($shortForm) != 1 ||
             $shortForm == '-' )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Illegal short form: $shortForm"
                ]);
        }
        $this->shortForm = $shortForm;
    }

    /**
     * Sets the type of this option, represented by of the strings 'switch',
     * 'boolean', 'int', 'float', or 'string'
     *
     * @param string $type
     */
    private function setType($type)
    {
        switch ($type) {
        case 'switch':
        case 'boolean':
        case 'int':
        case 'float':
        case 'string':
            break;
        default:
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Illegal type: $type"
                ]);
        }
        $this->type = $type;
    }

    /**
     * Sets the default value, if any, of this option
     *
     * @param mixed $default
     * @throws CodeRage\Error if the value is invalid
     */
    private function setDefault($default)
    {
        if ($this->type == 'switch' || $this->type == 'boolean') {
            if ($default !== false)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "Illegal default value for option $this"
                    ]);
        }
        $this->default = $default;
    }

    /**
     * Checks the given value
     *
     * @param string $longForm
     * @param mixed $value
     * @throws CodeRage\Error
     */
    private function testDataType($value)
    {
        if ($this->valueOptional() && $value == true)
            return;
        switch ($this->type()) {
        case 'switch':
        case 'boolean':
            if (!is_bool($value))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "The value of $this must be boolean"
                    ]);
            break;
        case 'int':
            if (!is_int($value))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "The value of $this must be an integer"
                    ]);
            break;
        case 'float':
            if (!is_int($value) && !is_float($value))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' =>
                            "The value of $this must be a floating point value"
                    ]);
            break;
        case 'string':
            if (!is_string($value))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'message' => "The value of $this must be a string"
                    ]);
            break;
        default:
            break;
        }
    }

    /**
     * The long form, if any, of this option
     *
     * @var string
     */
    private $longForm;

    /**
     * The short form, if any, of this option
     *
     * @var string
     */
    private $shortForm;

    /**
     * true if this option is required
     *
     * @var boolean
     */
    private $required;

    /**
     * The type of this option, represented by one of the strings 'switch',
     * 'boolean', 'int', 'float', or 'string'
     *
     * @var string
     */
    private $type;

    /**
     * The default value, if any, of this option
     *
     * @var mixed
     */
    private $default;

    /**
     * A brief descriptive name of this option, for use in error messages
     *
     * @var string
     */
    private $label;

    /**
     * The string used as the sample value of this when usage information is
     * displayed
     *
     * @var array
     */
    private $placeholder;

    /**
     * The description of this option
     *
     * @var array
     */
    private $description;

    /**
     * true if multiple occurrences of this option are permitted
     *
     * @var boolean
     */
    private $multiple;

    /**
     * true if the value of this option may be omitted
     *
     * @var boolean
     */
    private $valueOptional;

    /**
     * A callable taking an instance of CodeRage\Util\Command base as an
     * argument, used to execute this option
     *
     * @var callable
     */
    private $action;

    /**
     * The value of this option
     *
     * @var mixed
     */
    private $value;

    /**
     * true if this option has a value that was explicitly specified
     *
     * @var boolean
     */
    private $explicit;
}
