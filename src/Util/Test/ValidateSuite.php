<?php

/**
 * Defines the class CodeRage\Util\Test\ValidateSuite
 * 
 * File:        CodeRage/Util/Test/ValidateSuite.php
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

/**
 * @ignore
 */

/**
 * Test suite for the class CodeRage\Util\XmlEncoder
 *
 */
class ValidateSuite extends \CodeRage\Test\Suite {

    /**
     * Constructs an instance of CodeRage\Util\Test\ProcessOptionSuite
     */
    public function __construct()
    {
        parent::__construct(
            "Process Option Test Suite",
            "Tests the funtion CodeRage\Util\processOption and CodaRage\Util\\validate"
        );
        $this->initialize();
    }

    protected function suiteInitialize()
    {
        $this->constructValidateCases();
        $this->constructProcessOptionCases();
    }

    private function constructValidateCases()
    {
        $cases =
            [
                ['2019-04-12', 'date', 'date-with-hyphens', true],
                ['20190412', 'date', 'date-without-hyphens', true],
                ['2019-0412', 'date', 'date-with-single-hyphen-failure', false],
                ['201904-12', 'date', 'date-with-single-hyphen-failure-2', false],
                ['2019-02-29', 'date', 'date-with-non-existent-day', false],
                ['2019-13-29', 'date', 'date-with-invalid-month', false],
                ['2019-01-32', 'date', 'date-with-invalid-day', false],
                ['2019-04-12T12:02:59', 'datetime', 'datetime-with-hyphens-and-colons', true],
                ['2019-04-12T120259', 'datetime', 'datetime-with-hyphens-and-no-colons', true],
                ['20190412T12:02:59', 'datetime', 'datetime-with-colons-and-no-hypens', true],
                ['20190412T12:0259', 'datetime', 'datetime-with-single-colon-failure', false],
                ['20190412T1202:59', 'datetime', 'datetime-with-single-colon-failure-2', false],
                ['2019-04-12T12:02:59+07:19', 'datetime', 'datetime-with-timezone', true],
                ['2019-04-12T12:02:59-07:19', 'datetime', 'datetime-with-timezone-2', true],
                ['2019-04-12T12:02:59+0719', 'datetime', 'datetime-with-timezone-3', true],
                ['2019-04-12T12:02:59+07', 'datetime', 'datetime-with-timezone-4', true],
                ['2019-04-12T12:02:59Z', 'datetime', 'datetime-with-timezone-4', true],
                ['2019-04-12T12:02:59', 'datetime', 'datetime-with-hyphens-and-colons', true],
                ['2019-02-29T24:02:59', 'datetime', 'datetime-with-non-existent-day', false],
                ['2019-13-29T24:02:59', 'datetime', 'datetime-with-invalid-month', false],
                ['2019-04-32T24:02:59', 'datetime', 'datetime-with-invalid-day', false],
                ['2019-04-12T24:02:59', 'datetime', 'datetime-with-invalid-hour', false],
                ['2019-04-12T12:60:59', 'datetime', 'datetime-with-invalid-minute', false],
                ['2019-04-12T12:02:60', 'datetime', 'datetime-with-invalid-second', false],
                ['2019-04-12T12:02:59+24:19', 'datetime', 'datetime-with-invalid-utc-offset', false],
                ['2019-04-12T12:02:59+24', 'datetime', 'datetime-with-invalid-utc-offset', false],
                ['2019-04-12T12:02:59+07:60', 'datetime', 'datetime-with-invalid-utc-offset-2', false]
            ];
        foreach ($cases as $case)
            $this->add(new ValidateCase(...$case));
    }

    private function constructProcessOptionCases()
    {
        $name = 'xxxxx';
        $defaults = $this->values;
        while (count($defaults) > 3)
            array_pop($defaults);
        foreach ($this->values as $testValue) {
            foreach ($this->types as $type) {
                foreach ([true, false, null] as $required) {
                    foreach ($defaults as $defaultValue) {
                        foreach ([true, false] as $deprecated) {
                            $options =
                                [
                                    'name' => $name,
                                    'type' => $type,
                                    'deprecated' => $deprecated
                                ];
                            if (array_key_exists('value', $testValue)) {
                                $options['value'] = $testValue['value'];
                                $options['conforms'] = $testValue[$type];
                            }
                            if (array_key_exists('value', $defaultValue)) {
                                $options['default'] = $defaultValue['value'];
                                $options['defaultConforms'] =
                                    $defaultValue[$type];
                            }
                            if ($required !== null)
                                $options['required'] = $required;
                            $case = new ProcessOptionCase($options);
                            $this->add($case);
                        }
                    }
                }
            }
        }

        foreach ([true, false] as $deprecated) {

            // Test names other than $name
            $case =
                new ProcessOptionCase([ // Provided value is valid
                        'name' => 'other',
                        'type' => 'int',
                        'value' => 55,
                        'conforms' => true,
                        'deprecated' => $deprecated
                    ]);
            $this->add($case);
            $case =
                new ProcessOptionCase([ // Provided value is invalid
                        'name' => 'other',
                        'type' => 'string',
                        'value' => 55,
                        'conforms' => false,
                        'deprecated' => $deprecated
                    ]);
            $this->add($case);
            $case =
                new ProcessOptionCase([ // Default value is valid
                        'name' => 'other',
                        'type' => 'int',
                        'default' => 55,
                        'defaultConforms' => true,
                        'deprecated' => $deprecated
                    ]);
            $this->add($case);
            $case =
                new ProcessOptionCase([ // Default value is invalid
                        'name' => 'other',
                        'type' => 'string',
                        'default' => 55,
                        'defaultConforms' => false,
                        'deprecated' => $deprecated
                    ]);
            $this->add($case);

            // Test using labels
            $label = 'foo';
            $case =
                new ProcessOptionCase([ // Provided value is invalid
                        'name' => $name,
                        'type' => 'string',
                        'value' => 55,
                        'conforms' => false,
                        'label' => $label,
                        'deprecated' => $deprecated
                    ]);
            $this->add($case);
            $case =
                new ProcessOptionCase([ // Required value is absent
                        'name' => $name,
                        'type' => 'string',
                        'required' => true,
                        'label' => $label,
                        'deprecated' => $deprecated
                    ]);
            $this->add($case);
            $case =
                new ProcessOptionCase([ // Default value is invalid
                        'name' => $name,
                        'type' => 'string',
                        'default' => 55,
                        'defaultConforms' => false,
                        'label' => $label,
                        'deprecated' => $deprecated
                    ]);
            $this->add($case);
        }

        // Check invalid values for 'name'
        $case =
            new ProcessOptionErrorCase(
                    'null-as-name',
                    'Tests using null as name',
                    [[], null, 'string']
                );
        $this->add($case);
        $case =
            new ProcessOptionErrorCase(
                    'int-as-name',
                    'Tests using integer as name',
                    [[], 55, 'string']
                );
        $this->add($case);

        // Check invalid values for 'type'
        $case =
            new ProcessOptionErrorCase(
                    'null-as-type',
                    'Tests using null as type',
                    [[], $name, null]
                );
        $this->add($case);
        $case =
            new ProcessOptionErrorCase(
                    'int-as-type',
                    'Tests using integer as type',
                    [[], $name, 55]
                );
        $this->add($case);

        // Check invalid values for 'label'
        $case =
            new ProcessOptionErrorCase(
                    'int-as-label-deprecated',
                    'Tests using integer as label - deprecated interface',
                    [[], $name, 'string', 1]
                );
        $this->add($case);
        $case =
            new ProcessOptionErrorCase(
                    'int-as-label-current',
                    'Tests using integer as label - current interface',
                    [[], $name, 'string', ['label' => 1]]
                );
        $this->add($case);
        $case =
            new ProcessOptionErrorCase(
                    'indexed-array-as-label-deprecated',
                    'Tests using indexed array as label - deprecated interface',
                    [[], $name, 'string', [1, 2]]
                );
        $this->add($case);
        $case =
            new ProcessOptionErrorCase(
                    'indexed-array-as-label-current',
                    'Tests using indexed array as label - current interface',
                    [[], $name, 'string', ['label' => [1, 2]]]
                );
        $this->add($case);

        // Check invalid value for 'required'
        $case =
            new ProcessOptionErrorCase(
                    'string-as-required-deprecated',
                    'Tests using string as required flag - deprecated interface',
                    [[], $name, 'string', null, 'yes']
                );
        $this->add($case);
        $case =
            new ProcessOptionErrorCase(
                    'current-as-required-deprecated',
                    'Tests using string as required flag - current interface',
                    [[], $name, 'string', ['required' => 'yes']]
                );
        $this->add($case);
    }

    private function initialize()
    {
        $this->types =
            [
                'int', 'float', 'number', 'string', 'scalar', 'array', 'object',
                'callable', 'regex', 'DateTime', 'Blurg'
            ];
        $this->values =
            [
                'missingValue' => [],
                'nullValue'  =>
                    [
                        'value' => null,
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => false,
                        'scalar' => false,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'intValue'  =>
                    [
                        'value' => 10,
                        'boolean' => false,
                        'int' => true,
                        'float' => false,
                        'number' => true,
                        'string' => false,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'floatValue'  =>
                    [
                        'value' => 2.4,
                        'boolean' => false,
                        'int' => false,
                        'float' => true,
                        'number' => true,
                        'string' => false,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'numberValue1'  =>
                    [
                        'value' => 4,
                        'boolean' => false,
                        'int' => true,
                        'float' => false,
                        'number' => true,
                        'string' => false,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'numberValue2'  =>
                    [
                        'value' => 2.4,
                        'boolean' => false,
                        'int' => false,
                        'float' => true,
                        'number' => true,
                        'string' => false,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'stringValue'  =>
                    [
                        'value' => 'testString',
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => true,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'scalarValue1'  =>
                    [
                        'value' => 2,
                        'boolean' => false,
                        'int' => true,
                        'float' => false,
                        'number' => true,
                        'string' => false,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'scalarValue2'  =>
                    [
                        'value' => 2.4,
                        'boolean' => false,
                        'int' => false,
                        'float' => true,
                        'number' => true,
                        'string' => false,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'scalarValue3'  =>
                    [
                        'value' => true,
                        'boolean' => true,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => false,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'scalarValue4'  =>
                    [
                        'value' => 'testScalarString',
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => true,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'arrayValue1'  =>
                    [
                        'value' => ['a' ,'b'],
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => false,
                        'scalar' => false,
                        'array' => true,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'arrayValue2'  =>
                    [
                        'value' => ['a' => 'b'],
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => false,
                        'scalar' => false,
                        'array' => true,
                        'object' => false,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'objectValue'  =>
                    [
                        'value' => new stdClass,
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => false,
                        'scalar' => false,
                        'array' => false,
                        'object' => true,
                        'callable' => false,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'functionNameCallable'  =>
                    [
                        'value' => 'preg_match',
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => true,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => true,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'staticMethodNameCallable'  =>
                    [
                        'value' => 'DateTime::getLastErrors',
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => true,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => true,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'staticMethodArrayCallable'  =>
                    [
                        'value' => ['DateTime', 'getLastErrors'],
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => false,
                        'scalar' => false,
                        'array' => true,
                        'object' => false,
                        'callable' => true,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'instanceMethodArrayCallable'  =>
                    [
                        'value' => [new Exception, 'getMessage'],
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => false,
                        'scalar' => false,
                        'array' => true,
                        'object' => false,
                        'callable' => true,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'anonymousCallable'  =>
                    [
                        'value' => function() { },
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => false,
                        'scalar' => false,
                        'array' => false,
                        'object' => true,
                        'callable' => true,
                        'regex' => false,
                        'DateTime' => false,
                        'Blurg' => false
                    ],
                'regexPattern'  =>
                    [
                        'value' => '/[A-Z]*/',
                        'boolean' => false,
                        'int' => false,
                        'float' => false,
                        'number' => false,
                        'string' => true,
                        'scalar' => true,
                        'array' => false,
                        'object' => false,
                        'callable' => false,
                        'regex' => true,
                        'DateTime' => false,
                        'Blurg' => false
                    ]
            ];
    }

    /**
     * The list of types to be passed as the $type argument to validate() and
     * processOptions()
     *
     * @var array
     */
    private $types;

    /**
     * A collection of named values together with type information
     *
     * @var array
     */
    private $values;
}
