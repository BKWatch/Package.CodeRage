<?php

/**
 * Defines the interface CodeRage\Tool\Robot\RequestLogger and the class
 * CodeRage\Tool\Roboto\DefaultRequestLogger
 *
 * File:        CodeRage/Tool/Robot/RequestLogger.php
 * Date:        Fri Jan 12 16:39:25 UTC 2018
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2018 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Tool\Robot;

use Psr\Http\Message\UriInterface as Uri;
use Throwable;
use CodeRage\Tool\Tool;

/**
 * Interface for components that are notified before and after each request made
 * by an instance of CodeRage\Tool\Robot
 */
interface RequestLogger {

    /**
     * Called immediately before the given robot makes a request
     *
     * @param CodeRage\Tool\Tool $robot The robot
     * @param string $method The HTTP method name
     * @param Psr\Http\Message\UriInterface $uri The URI
     * @throws Exception if the request should be aborted
     * @throws CodeRage\Error with status 'RETRY' to repeat the operation
     */
    function preRequest(Tool $robot, string $method, Uri $uri) : void;

    /**
     * Called immediately after the given robot makes a request
     *
     * @param CodeRage\Tool\Tool $robot The robot
     * @param string $method The HTTP method name
     * @param Psr\Http\Message\UriInterface $uri The URI
     * @param Throwable $error The exception thrown by the request, if any
     * @throws Exception if an error occurs and $error is null; if $error is
     *   non-null, must not throw an exception
     */
    function postRequest(Tool $robot, string $method, Uri $uri,
        ?Throwable $error = null)  : void;
}
