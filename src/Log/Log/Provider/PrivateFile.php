<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\FileHandle
 *
 * File:        CodeRage/Log/Provider/PrivateFile.php
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
 * Subclass of CodeRage\Log\Provider\FileHandle that writes log entries to a file
 */
final class PrivateFile extends FileHandle {

    /**
     * Constructs an instance of CodeRage\Log\Provider\PrivateFile
     *
     * @param array $options The options array; supports the following options:
     *   path - A file pathname
     *   format - A bitwise OR of zero or more of the constants
     *     CodeRage\Log\Entry::XXX
     */
    public function __construct($options)
    {
        if (!isset($options['path']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing file pathname'
                ]);
        $dir = dirname($options['path']);
        if (!file_exists($dir))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "No such directory: $dir"
                ]);
        if (!is_dir($dir))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "The file '$dir' is not a directory"
                ]);
        $handler = new \CodeRage\Util\ErrorHandler;
        $file = $handler->_fopen($options['path'], 'w');
        if ($file === false)
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'message' => $handler->formatError()
                ]);
        parent::__construct([
            'file' => $file,
            'format' => isset($options['format']) ?
                $options['format'] :
                null
        ]);
    }

    /**
     * Returns "private_file"
     *
     * @return string
     */
    public function name() { return 'private_file'; }
}
