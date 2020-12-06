<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\Console
 *
 * File:        CodeRage/Log/Provider/Console.php
 * Date:        Thu Jan 31 20:33:13 EST 2013
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log\Provider;

use CodeRage\Error;

/**
 * @ignore
 */

/**
 * Subclass of CodeRage\Log\Provider\FileHandle that writes log entries to standard
 * output or standard error
 */
final class Console extends FileHandle {

    /**
     * Constructs an instance of CodeRage\Log\Provider\Console
     *
     * @param array $options The options array; supports the following options:
     *   stream - One of 'stdout' or 'stderr'
     *   format - A bitwise OR of zero or more of the constants
     *     CodeRage\Log\Entry::XXX
     */
    public function __construct($options)
    {
        if (!isset($options['stream']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing stream type'
                ]);
        if ($options['stream'] != 'stdout' && $options['stream'] != 'stderr')
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "Invalid stream type: {$options['stream']}"
                ]);
        parent::__construct([
            'file' => $options['stream'] == 'stdout' ?
                fopen('php://stdout', 'w') :
                fopen('php://stderr', 'w'),
            'format' => isset($options['format']) ?
                $options['format'] :
                0
        ]);
    }

    /**
     * Returns "console"
     *
     * @return string
     */
    public function name() { return 'console'; }
}
