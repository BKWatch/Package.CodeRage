<?php

/**
 * Defines the class CodeRage\Test\SuitePhpError
 * 
 * File:        CodeRage/Test/SuitePhpError.php
 * Date:        Sun Jul 15 00:28:53 EDT 2012
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Test;

use Exception;
use Throwable;
use CodeRage\Error;
use CodeRage\Util\ErrorHandler;
use CodeRage\Util\Time;


class SuitePhpError extends Error {

    /**
     * Constructs a CodeRage\Test\SuitePhpError.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    public function __construct($errno, $errstr, $errfile, $errline)
    {
        $msg =
            ErrorHandler::errorCategory($errno) .
            ": $errstr in $errfile on line $errline";
        parent::__construct($msg);
    }
}
