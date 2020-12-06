<?php

/**
 * Defines the class CodeRage\Tool\Test\MockResponse
 *
 * File:        CodeRage/Tool/Test/MockResponse.php
 * Date:        Fri Jan 19 16:03:39 UTC 2018
 * Notice:      This document contains confidential information and
 *              trade secrets
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Test;

use Exception;
use CodeRage\Error;
use CodeRage\Util\Args;


/**
 * Contains machinery used by CodeRage\Tool\Test\__www__\mock-response.php and
 * by CodeRage\Tool\Test\RobotSuite
 */
class MockResponse {

    /**
     * Default HTTP status code
     *
     * @var int
     */
    const DEFAULT_STATUS_CODE = 200;

    /**
     * Maps HTTP status codes to standard reason phrases
     *
     * @var array
     */
    const DEFAULT_REASON_PHRASE =
        [
            '100' => 'Continue',
            '101' => 'Switching Protocols',
            '102' => 'Processing',
            '200' => 'OK',
            '201' => 'Created',
            '202' => 'Accepted',
            '203' => 'Non-Authoritative Information',
            '204' => 'No Content',
            '205' => 'Reset Content',
            '206' => 'Partial Content',
            '207' => 'Multi-Status',
            '208' => 'Already Reported',
            '226' => 'IM Used',
            '300' => 'Multiple Choices',
            '301' => 'Moved Permanently',
            '302' => 'Found',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '306' => 'Switch Proxy',
            '307' => 'Temporary Redirect',
            '308' => 'Permanent Redirect',
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '402' => 'Payment Required',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '406' => 'Not Acceptable',
            '407' => 'Proxy Authentication Required',
            '408' => 'Request Timeout',
            '409' => 'Conflict',
            '410' => 'Gone',
            '411' => 'Length Required',
            '412' => 'Precondition Failed',
            '413' => 'Request Entity Too Large',
            '414' => 'Request-URI Too Long',
            '415' => 'Unsupported Media Type',
            '416' => 'Requested Range Not Satisfiable',
            '417' => 'Expectation Failed',
            '418' => "I'm a teapot",
            '419' => 'Authentication Timeout',
            '420' => 'Method Failure',
            '422' => 'Unprocessable Entity',
            '423' => 'Locked',
            '424' => 'Failed Dependency',
            '425' => 'Unordered Collection',
            '426' => 'Upgrade Required',
            '428' => 'Precondition Required',
            '429' => 'Too Many Requests',
            '431' => 'Request Header Fields Too Large',
            '440' => 'Login Timeout',
            '444' => 'No Response',
            '449' => 'Retry With',
            '450' => 'Blocked by Windows Parental Controls',
            '451' => 'Unavailable For Legal Reasons',
            '494' => 'Request Header Too Large',
            '495' => 'Cert Error',
            '496' => 'No Cert',
            '496' => 'HTTP to HTTPS',
            '499' => 'Client Closed Request',
            '500' => 'Internal Server Error',
            '501' => 'Not Implemented',
            '502' => 'Bad Gateway',
            '503' => 'Service Unavailable',
            '504' => 'Gateway Timeout',
            '505' => 'HTTP Version Not Supported',
            '506' => 'Variant Also Negotiates',
            '507' => 'Insufficient Storage',
            '508' => 'Loop Detected',
            '509' => 'Bandwidth Limit Exceeded',
            '510' => 'Not Extended',
            '511' => 'Network Authentication Required',
            '520' => 'Origin Error',
            '522' => 'Connection timed out',
            '523' => 'Proxy Declined Request',
            '524' => 'A timeout occurred',
            '598' => 'Network read timeout error',
            '599' => 'Network connect timeout error'
        ];

    /**
     * Default "method" attribute for form elements
     *
     * @var string
     */
    const DEFAULT_FORM_METHOD = 'get';

    /**
     * Default "enctype" attribuet  for form elements
     *
     * @var string
     */
    const DEFAULT_FORM_ENCTYPE = 'application/x-www-form-urlencoded';

    /**
     * Regex matching boolean values
     *
     * @var string
     */
    const MATCH_BOOLEAN = '/^(1|0|true|false)$/';

    /**
     * Top-level property names for JSON data posted to mock-response.php
     *
     * @var array
     */
    const ALLOWED_OPTIONS =
        [
            'statusCode' => 1,
            'reasonPhrase' => 1,
            'contentType' => 1,
            'headers' => 1,
            'cookies' => 1,
            'sleep' => 1,
            'body' => 1,
            'forms' => 1,
            'ifRequestedAfter' => 1,
            'ifRequestedBefore' => 1
        ];

    /**
     * Supported HTML input types
     *
     * @var array
     */
    const SUPPORTED_INPUT_TYPES =
        [
            'text' => 1,
            'password' => 1,
            'checkbox' => 1,
            'radio' => 1,
            'submit' => 1,
            'hidden' => 1,
            'select' => 1,
            'file' => 1
        ];

    /**
     * Default content type for HTTP responses generated by mock-response.php
     *
     * @var string
     */
    const TEXT_HTML_UTF8 = 'text/html; charset=utf-8';

    /**
     * Regular expression matching text/html content type, ignoring parameters
     *
     * @var string
     */
    const MATCH_HTML = '#^text/html\b#';

    /**
     * Regular expression for validating custom attribute names
     *
     * @var string
     */
    const MATCH_ATTRIBUTE = '/^[-.:_a-z0-9]+$/';

    /**
     * Validates and processes JSON data posted to mock-response.php; for
     * documentation, request mock-response.php?help
     *
     * @param array $options The options array
     * @throws Exception
     */
    public static function processOptions(array &$options)
    {
        Args::check($options, 'map', 'options');
        foreach ($options as $n => $v)
            if (!array_key_exists($n, self::ALLOWED_OPTIONS))
                throw new Exception("Unsupported option: $n");
        Args::checkKey($options, 'statusCode', 'int|string', [
            'label' => 'status code',
            'default' => self::DEFAULT_STATUS_CODE
        ]);
        $statusCode = $options['statusCode'];
        if ($statusCode < 100 || $statusCode >= 600)
            throw new Exception("Invalid status code: $statusCode");
        Args::checkKey($options, 'reasonPhrase', 'string', [
            'label' => 'reasonPhrase'
        ]);
        if ( !isset($options['reasonPhrase']) &&
             array_key_exists($statusCode, self::DEFAULT_REASON_PHRASE) )
        {
            $options['reasonPhrase'] =
                self::DEFAULT_REASON_PHRASE[$statusCode] ?? null;
        }
        Args::checkKey($options, 'contentType', 'string', [
            'label' => 'content type',
            'default' => MockResponse::TEXT_HTML_UTF8
        ]);
        Args::checkKey($options, 'headers', 'map', [
            'label' => 'headers array',
            'default' => []
        ]);
        foreach ($options['headers'] as $n => $v)
            Args::check($header, 'string', "'$n' header");
        Args::checkKey($options, 'cookies', 'list', [
            'label' => 'cookies array',
            'default' => []
        ]);
        foreach ($options['cookies'] as $cookie) {
            Args::checkKey($cookie, 'name', 'string', [
                'label' => 'cookies name',
                'required' => true
            ]);
            Args::checkKey($cookie, 'value', 'string', [
                'label' => 'cookies value',
                'required' => true
            ]);
        }
        Args::checkKey($options, 'sleep', 'int');
        if (isset($options['sleep']) && $options['sleep'] <= 0)
            throw new Exception("The option 'sleep' must be a positive integer");
        Args::checkKey($options, 'body', 'string');
        Args::checkKey($options, 'title', 'string');
        Args::checkKey($options, 'forms', 'list');
        Args::checkKey($options, 'ifRequestedAfter', 'int|string', [
            'label' => "'ifRequestedAfter' option"
        ]);
        if (!isset($options['ifRequestedAfter']))
            $options['ifRequestedAfter'] = -INF;
        if (is_string($options['ifRequestedAfter'])) {
            if (!ctype_digit($options['ifRequestedAfter']))
                throw new
                    Exception(
                        "Invalid 'ifRequestedAfter': expected integer; found " .
                        $options['ifRequestedAfter']
                    );
            $options['ifRequestedAfter'] = (int) $options['ifRequestedAfter'];
        }
        Args::checkKey($options, 'ifRequestedBefore', 'int|string', [
            'label' => "'ifRequestedBefore' option"
        ]);
        if (!isset($options['ifRequestedBefore']))
            $options['ifRequestedBefore'] = INF;
        if (is_string($options['ifRequestedBefore'])) {
            if (!ctype_digit($options['ifRequestedBefore']))
                throw new
                    Exception(
                        "Invalid 'ifRequestedBefore': expected integer; found " .
                        $options['ifRequestedBefore']
                    );
            $options['ifRequestedBefore'] = (int) $options['ifRequestedBefore'];
        }
        if ($options['ifRequestedBefore'] <= $options['ifRequestedAfter'])
            throw new
                Exception(
                    "The option 'ifRequestedAfter' cannot be greater than " .
                    "'ifRequestedBefore' option"
                );
        if (isset($options['forms'])) {
            foreach (['sleep', 'body'] as $opt)
                if (isset($options[$opt]))
                    throw new
                        Exception(
                            "The options 'forms' and '$opt' are incompatible"
                        );
            if (!preg_match(self::MATCH_HTML, $options['contentType']))
                throw new
                    Exception(
                        "The option 'forms' is incompatible with content type " .
                        $options['contentType']
                    );
            foreach ($options['forms'] as $i => &$form)
                self::processForm($form, $i);
        }
    }

    /**
     * Processes and validates elements of the 'forms' array posted to
     * mock-response.php; for documentation, request mock-response.php?help
     *
     * @param array $form A form object, represented as an associative array
     * @param itn $index The position of $form within the 'forms' array
     * @throws Exception
     */
    private static function processForm(array &$form, $index)
    {
        $title = "form at position $index";
        Args::check($form, 'map', $title);
        Args::checkKey($form, 'name', 'string', [
            'label' => "'name' property of $title",
        ]);
        Args::checkKey($form, 'id', 'string', [
            'label' => "'id' property of $title",
        ]);
        Args::checkKey($form, 'method', 'string', [
            'label' => "'method' property of $title",
            'default' => self::DEFAULT_FORM_METHOD
        ]);
        $method = $form['method'];
        if ($method != 'get' && $method != 'post')
            throw new
                Exception("Unsupported 'method' property of $title: $method");
        Args::checkKey($form, 'enctype', 'string', [
            'label' => "'enctype' property of $title",
            'default' => self::DEFAULT_FORM_ENCTYPE
        ]);
        $enctype = $form['enctype'];
        if ( $enctype != 'application/x-www-form-urlencoded' &&
             $enctype != 'multipart/form-data' )
        {
            throw new
                Exception("Unsupported 'enctype' property of $title: $enctype");
        }
        Args::checkKey($form, 'action', 'string', [
            'label' => "'action' property of $title"
        ]);
        Args::checkKey($form, 'inputs', 'list', [
            'label' => "'inputs' property of $title",
            'default' => []
        ]);
        foreach ($form['inputs'] as &$input) {
            Args::checkKey($input, 'name', 'string', [
                'label' => "'name' property of input in $title",
                'required' => true
            ]);
            $label = "'{$input['name']}' input in $title";
            Args::checkKey($input, 'type', 'string', [
                'label' => "'type' property of $label",
                'required' => true
            ]);
            if (!array_key_exists($input['type'], self::SUPPORTED_INPUT_TYPES))
                throw new
                    Exception(
                        "Unsupported 'type' property of $label: " .
                        $input['type']
                    );
            Args::checkKey($input, 'id', 'string', [
                'label' => "'id' property of $label",
            ]);
            Args::checkKey($input, 'label', 'string', [
                'label' => "'label' property of $label",
            ]);
            Args::checkKey($input, 'attributes', 'map', [
                'label' => "'attributes' property of $label",
                'default' => []
            ]);
            foreach ($input['attributes'] as $n => $v) {
                Args::check($v, 'string', "'$n' attribute of $label");
                if (!preg_match(self::MATCH_ATTRIBUTE, $v))
                    throw new
                        Exception("Invalid '$n' attribute of $label: $v");
            }

            // Process options specific to input type 'select' and 'file'
            if ($input['type'] != 'select' && $input['type'] != 'file') {
                if (isset($input['multiple']))
                    throw new
                        Exception(
                            "The option 'multiple' is incompatible" .
                            " with input type '{$input['type']}'"
                        );
            } else {
                Args::checkKey($input, 'multiple', 'string|boolean', [
                    'label' => "'multiple' property of $label",
                    'default' => false
                ]);
                if (is_string($input['multiple']))
                    $input['multiple'] =
                        self::processBoolean(
                            $input['multiple'], "'multiple' property of $label"
                        );
            }

            // Process options specific to input type 'select'
            if ($input['type'] != 'select') {
                if (isset($input['options']))
                    throw new
                        Exception(
                            "The option 'options' is incompatible" .
                            " with input type '{$input['type']}'"
                        );

            } else {
                Args::checkKey($input, 'options', 'array', [
                    'label' => "options of $label",
                    'required' => true
                ]);
                $selected = false;
                foreach ($input['options'] as $option) {
                    Args::check($option, 'array', "'option' of $label");
                    Args::checkKey($option, 'value', 'string', [
                        'label' => "'value' property of option of $label",
                        'required' => true
                    ]);
                    Args::checkKey($option, 'label', 'string', [
                        'label' => "'label' property of option of $label",
                        'required' => true
                    ]);
                    Args::checkKey($option, 'selected', 'string|boolean', [
                        'label' => "'selected' property of option of $label",
                        'default' => 'false'
                    ]);
                    if (is_string($option['selected']))
                        $option['selected'] =
                            self::processBoolean(
                                $option['selected'],
                                "'selected' property of option of $label"
                            );
                    if (!$input['multiple'] && $option['selected']) {
                        if ($selected)
                            throw new
                                Exception(
                                    'At most one option may be selected for ' .
                                    $label
                                );
                        $selected = true;
                    }
                }
            }

            // Process options specific to input type 'file'
            if ($input['type'] != 'file') {
                if (isset($input['accept']))
                    throw new
                        Exception(
                            "The option 'accept' is incompatible" .
                            " with input type '{$input['type']}'"
                        );
            } else {
                Args::checkKey($input, 'accept', 'string', [
                    'label' => "'accept' property of $label",
                    'required' => false
                ]);
            }
        }
    }

    /**
     * Process string boolean values
     *
     * @param string $value A boolean value represented as a string
     * @param string $label A descriptive label for use in error messages
     * @return boolean The result of converting $value to a boolean
     * @throws Exception
     */
    private static function processBoolean($value, $label)
    {
        if (!preg_match(self::MATCH_BOOLEAN, $value))
            throw new
                Exception(
                    "Invalid $label: expected 'boolean'; found " .
                    Error::formatValue($value)
                );
        return $value == 'true' || $value == '1';
    }
}
