<?php

/**
 * Defines the class CodeRage\Build\ConsoleDialog
 *
 * File:        CodeRage/Build/ConsoleDialog.php
 * Date:        Mon Feb 25 12:25:33 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\Error;
use CodeRage\Util\Os;

/**
 * Prompts the user for input on the console.
 *
 */
class ConsoleDialog implements Dialog {

    /**
     * The value returned by maxQueries().
     *
     * @var int
     */
    const MAX_QUERIES = 10;

    /**
     * The underlying input stream
     *
     * @var resource
     */
    private $console;

    /**
     * Constructs a CodeRage\Build\ConsoleDialog.
     *
     * @throws CodeRage\Error
     */
    function __construct()
    {
        $file = Os::type() == 'posix' ? '/dev/tty' : '\con';
        $handler = new \CodeRage\Util\ErrorHandler;
        $this->console = $handler->_fopen($file, 'r');
        if ($handler->errno())
            $this->console = $handler->_fopen('php://stdin', 'r');
        if ($handler->errno())
            throw new Error(['message' => $handler->formatError('Failed opening console')]);
    }

    /**
     * Obtains input from the user.
     *
     * @param string $label The text to display to the user.
     * @return mixed The value, if any, obtained from the user.
     */
    function query($label)
    {
        $result = fgets($this->console, 1000);
        if ($result === false)
            throw new Error(['message' => 'Failed reading from console']);
        return ($result = trim($result)) ? $result : null;
    }

    /**
     * Returns the maximum number of times query() should be called to fulfill a
     * single request for information.
     *
     * @return int
     */
    function maxQueries() { return self::MAX_QUERIES; }
}
