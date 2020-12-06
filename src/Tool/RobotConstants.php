<?php

/**
 * Defines the class CodeRage\Tool\RobotConstants
 *
 * File:        CodeRage/Tool/RobotConstants.php
 * Date:        Sun Jan 14 20:43:12 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool;


/**
 * Container for constants used by CodeRage\Tool\Robot
 */
final class RobotConstants {

    /**
     * The default values of the "User-Agent" header
     *
     * @var string
     */
    const DEFAULT_USER_AGENT =
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like ' .
        'Gecko) Chrome/33.0.1750.117 Safari/537.36';

    /**
     * The default values of the "Accept" header
     *
     * @var string
     */
    const DEFAULT_ACCEPT =
        'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,' .
        'text/plain;q=0.8,image/png,*/*;q=0.5';

    /**
     * The default values of the "Accept-Language" header
     *
     * @var string
     */
    const DEFAULT_ACCEPT_LANGUAGE = 'en-us,en;q=0.5';

    /**
     * The default value of the "verifyCertificate" option
     *
     * @var boolean
     */
    const DEFAULT_VERIFY_CERTIFICATE = true;

    /**
     * The default request timeout, in seconds
     *
     * @var int
     */
    const DEFAULT_TIMEOUT = 300;

    /**
     * The default number of times to attempt a request before aborting
     *
     * @var int
     */
    const DEFAULT_FETCH_ATTEMPTS = 5;

    /**
     * The default number of microseconds to wait after an initial failed
     * request before a second attempt
     *
     * @var int
     */
    const DEFAULT_FETCH_SLEEP = 1000000;

    /**
     * The default multipler for use in exponential backoff when a request
     * fails
     *
     * @var float
     */
    const DEFAULT_BACKOFF_MULTIPLIER = 3.0;

    /**
     * Associative arrays whose keys are the collection of Guzzle request
     * options managed by the BrowserKit client
     *
     * @var array
     */
    const NON_CONFIGURATBLE_REQUEST_OPTIONS =
        [
            'allow_redirects' => 1,
            'body' => 1,
            'cookies' => 1,
            'form_params' => 1,
            'multipart' => 1
        ];

    /**
     * Regular expression for validating "name" and "id" attributes
     *
     * @var string
     */
    const MATCH_ATTRIBUTE = '/^[-.:_a-z0-9]+$/i';

    /**
     * Associative array whose keys are the set of HTML button types
     *
     * @var array
     */
    const BUTTON_TYPES = ['button' => 1, 'submit' => 1, 'reset' => 1];

    /**
     * Options for button selection
     *
     * @var array
     */
    const BUTTON_OPTIONS =
        [
            'buttonName' => ['attribute' => 'name', 'label' => 'name'],
            'buttonId' => ['attribute' => 'id', 'label' => 'ID'],
            'buttonValue' => ['attribute' => 'value', 'label' => 'value']
        ];
}
