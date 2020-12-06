<?php

/**
 * Defines the class CodeRage\Util\Test\ProcessOptionCase
 * 
 * File:        CodeRage/Util/Test/ProcessOptionCase.php
 * Date:        Mon Aug 15 13:34:26 EDT 2016
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Fallak Asad
 * @license     All rights reserved
 */

namespace CodeRage\Util\Test;

use Exception;
use stdClass;
use Throwable;
use CodeRage\Error;
use CodeRage\Test\Assert;
use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Test case that invokes processOption() and verifies that the outcome is as
 * expected
 */
class ProcessOptionCase extends \CodeRage\Test\Case_ {

    /**
     * Constructs an instance of CodeRage\Util\Test\ProcessOptionCase
     *
     * @param array $options The options array; supports the following options:
     *     name - The $name argument to processOption()
     *     type - The $type argument to processOption()
     *     label - The value of the "label" parameter to processOption(); if
     *       not present, no "label" parameter will be passed to
     *       processOption()
     *     value - The value to be stored in the options array with the key
     *       specified with the "name" option; if this option is not present,
     *       an empty options array will be passed to processOption()
     *     conforms - true if the value of the "value" option conforms to the
     *       value of the "type" option
     *     default - The value of the "default" parameter to processOption(); if
     *       not present, no "default" parameter will be passed to
     *       processOption()
     *     defaultConforms - true if the value of the "default" option conforms
     *       to the value of the "type" option
     *     required - The "required" parameter to processOption(); if not
     *       present, no "required" parameter will be passed to processOption()
     *     deprecated - True to use the deprecated interface to processOption()
     * @throws CodeRage\Error
     */
    public function __construct(array $options)
    {
        if (!isset($options['name']))
            throw new
                Error([
                    'status' => 'MISSTING_PARAMETER',
                    'message' => 'Missing option name'
                ]);
        if (!isset($options['type']))
            throw new
                Error([
                    'status' => 'MISSTING_PARAMETER',
                    'message' => 'Missing type name'
                ]);
        if ( isset($options['value']) &&
             $options['value'] !== null &&
             !isset($options['conforms']) )
        {
            throw new
                Error([
                    'status' => 'MISSTING_PARAMETER',
                    'message' => 'Missing conforms flag'
                ]);
        }
        if ( isset($options['default']) &&
             $options['default'] !== null &&
             !isset($options['defaultConforms']) )
        {
            throw new
                Error([
                    'status' => 'MISSTING_PARAMETER',
                    'message' => 'Missing defaultConforms flag'
                ]);
        }
        if (!isset($options['deprecated']))
            throw new
                Error([
                    'status' => 'MISSTING_PARAMETER',
                    'message' => 'Missing deprecated flag'
                ]);
        $labels = [];
        foreach ($options as $n => $v) {
            $v = Error::formatValue($v);
            $v = trim(preg_replace('/[^._a-z0-9]+/i', '-', $v), '-');
            $labels[] = "$n:$v";
        }
        $name = 'process-option-case[' . join(';', $labels) . ']';
        $description = 'Process Options case with ' . join('; ', $labels);
        parent::__construct($name, $description);
        $this->options = $options;
    }

    protected function doExecute($ignore)
    {
        $name = $this->options['name'];
        $type = $this->options['type'];
        $hasValue = array_key_exists('value', $this->options);
        $options = $hasValue ?
            [$name => $this->options['value']] :
            [];
        $hasLabel = array_key_exists('label', $this->options);
        $hasRequired = array_key_exists('required', $this->options);
        $hasDefault = array_key_exists('default', $this->options);
        $error = null;
        try {
            if ($this->options['deprecated']) {
                if ($hasRequired && !$hasLabel || $hasDefault && !$hasRequired)
                    return;  // This case can't happen with old interface
                $args = [$name, $type];
                if ($hasLabel)
                    $args[] = $this->options['label'];
                if ($hasRequired)
                    $args[] = $this->options['required'];
                if ($hasLabel)
                    $args[] = $this->options['deprecated'];
                Args::checkKey($options, ...$args);
            } else {
                $params = [];
                if ($hasLabel)
                    $params['label'] = $this->options['label'];
                if ($hasRequired)
                    $params['required'] = $this->options['required'];
                if ($hasDefault)
                    $params['default'] = $this->options['default'];
                $result = Args::checkKey($options, $name, $type, $params);
            }
        } catch (Error $e) { // Any exception other than Error is a failure
            $error = $e;
        }

        // Check for label in error message
        if ($error) {
            $label = $hasLabel ?
                $this->options['label'] :
                $name;
            if ($error->status() == 'MISSING_PARAMETER')
                Assert::equal(
                    $error->message(),
                    "Missing $label",
                    'Missing label from expected exception message'
                );
            if ($error->status() == 'INVALID_PARAMETER')
                Assert::equal(
                    $error->message(),
                    "Invalid $label",
                    'Missing label from expected exception message'
                );
        }

        // Analyze results
        $foundStatus = $error !== null ?
            $error->status() :
            null;
        $expectedStatus = null;
        $missingValue = !$hasValue || $this->options['value'] === null;
        if ($hasRequired && $this->options['required'] && $missingValue) {
            $expectedStatus = 'MISSING_PARAMETER';
        } elseif (!$missingValue && !$this->options['conforms'] )
        {
            $expectedStatus = 'INVALID_PARAMETER';
        } elseif ( $missingValue &&
                   $hasDefault &&
                   $this->options['default'] !== null &&
                   !$this->options['defaultConforms'] )
        {
            $expectedStatus = 'INVALID_PARAMETER';
        }
        if ($foundStatus !== $expectedStatus) {
            if ($expectedStatus === null)
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "Caught unexpected exception with status code " .
                            "'$foundStatus'"
                    ]);
            if ($foundStatus === null)
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "Expected exception with status code " .
                            "'$expectedStatus'; none caught"
                    ]);
            throw new
                Error([
                    'status' => 'ASSERTION_FAILED',
                    'message' =>
                        "Expected exception with status code " .
                        "'$expectedStatus'; caught exception with status " .
                        "code '$foundStatus'"
                ]);
        } elseif ($error !== null) {
            echo "Caught expected exception with status code '$foundStatus' " .
                 "and message '" . $error->message() . "'";
        }
        if ($error === null && !$hasValue && $hasDefault) {
            if (!array_key_exists($name, $options))
                throw new
                    Error([
                        'status' => 'ASSERTION_FAILED',
                        'message' =>
                            "Option '$name' missing from option array despite " .
                            'having a default value'
                    ]);
            Assert::equal(
                $options[$name],
                $this->options['default'],
                "Wrong default value applied for option '$name'"
            );
        }
    }

    /**
     * Test case parameters
     *
     * @var array
     */
    private $options;
}
