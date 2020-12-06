<?php

/**
 * Defines the interface CodeRage\Util\SystemHandle
 * 
 * File:        CodeRage/Util/SystemHandle.php
 * Date:        Tue Feb  7 06:03:23 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Util;

use CodeRage\Error;
use CodeRage\Log;


/**
 * Represents an interface to system services
 */
interface SystemHandle {

    /**
     * Returns an instance of CodeRage\Config
     *
     * @return CodeRage\Config
     */
    function config();

    /**
     * Returns a database connection
     *
     * @return CodeRage\Db
     */
    function db();

    /**
     * Returns a log
     *
     * @return CodeRage\Log
     */
    function log();

    /**
     * Returns the associated session, if any
     *
     * @return CodeRage\Access\Session
     */
    function session();

    /**
     * Sets or clears the associated session
     *
     * @param CodeRage\Access\Session $session The session
     */
    function setSession($session);

    /**
     * Logs a message
     *
     * @param string $msg The log message
     */
    function logMessage($msg);

    /**
     * Logs a warning
     *
     * @param string $msg The log message
     */
    function logWarning($msg);

    /**
     * Logs an error
     *
     * @param string $msg The log message or an instance of Throwable
     */
    function logError($msg);

    /**
     * Logs a critical error
     *
     * @param string $msg The log message
     */
    function logCritical($msg);

    /**
     * Returns a newly constructed instance of the specified class, or a
     * component with a compatible interface
     *
     * @param array $options The options array; supports the following options:
     *   class - A class name, specified as a sequence of identifiers separated
     *     by dots (required)
     *   params - An associative array of constructor parameters (optional)
     * @throws CodeRage\Error if the component class cannot be located.
     */
    function loadComponent($options);
}
