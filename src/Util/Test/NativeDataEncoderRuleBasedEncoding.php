<?php

/**
 * Defines the class CodeRage\Util\Test\NativeDataEncoderRuleBasedEncoding
 * 
 * File:        CodeRage/Util/Test/NativeDataEncoderRuleBasedEncoding.php
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
 * Base class for CodeRage\Util\Test\NativeDataEncoderCustomEncoding and
 * CodeRage\Util\Test\NativeDataEncoderPropertyBasedEncoding
 */
class NativeDataEncoderRuleBasedEncoding {

    /**
     * Constructs a CodeRage\Util\Test\NativeDataEncoderRuleBasedEncoding
     */
    public function __construct()
    {
        $this->encoding = $encoding;
    }

    /**
     * Adds a rule for use by execute(). Rules are evaluated in the order they
     * are added.
     *
     * @param string $pattern A regular expression to be matched against a
     *   native data encoder's options array, with the options encoding in the
     *   form "n1=v1,n2=v2,..."
     * @param mixed $value The value to be returned by execute() if $pattern
     *   matches the native data encoder's options array
     */
    public function addRule($pattern, $value)
    {
        $this->rules[] = [$pattern, $value];
    }

    /**
     * Adds the given value as the return value of the named property accessor
     *
     * @param string $name The name of a property-accessor method that will
     *   return $value
     * @param mixed $value The value to be returned
     */
    public function addProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * Sets this objects properties
     *
     * @param array $properties an associative array mapping names of property-
     *   accessor methods to return values
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * Iterates over the collection of rules, which must be non-empty, and
     * returns the value associated with the first pattern that matches the
     * given encoder.
     */
    protected function execute(NativeDataEncoder $encoder)
    {
        if (!sizeof($this->rules))
            throw new
                Error([
                    'status' => 'STATE_ERROR',
                    'message' => 'No rules specified'
                ]);
        $optionsList = [];
        foreach ($encoder->options() as $n => $v)
            $optionsList[] = "$n=$v";
        $options = join(',', $optionsList);
        foreach ($this->rules as $r) {
            list($pattern, $value) = $r;
            if (preg_match($pattern, $options))
                return $value;
        }
        throw new
            Error([
                'status' => 'INVALID_PARAMETER',
                'message' => 'No rule matches the input'
            ]);
    }

    public function __call($method, $arguments)
    {
        $length = sizeof($arguments);
        if ($n = sizeof($arguments))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Expected zero arguments; found $n"
                ]);
        if (isset($this->properties[$method]))
            return $this->properties[$method];
        throw new
            Error([
                'status' => 'UNSUPPORTED_OPERATIONS',
                'message' => "No such method: $method"
            ]);
    }

    /**
     * An associative array of method names and return values, used to implement
     * __call()
     *
     * @var array
     */
    private $properties = [];

    /**
     * An array of ($pattern, $value) pairs, where $pattern is a regular
     * expression
     *
     * @var array
     */
    private $rules = [];
}
