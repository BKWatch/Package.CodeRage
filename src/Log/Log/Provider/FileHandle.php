<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\FileHandle
 *
 * File:        CodeRage/Log/Provider/FileHandle.php
 * Date:        Thu Jan 31 20:33:13 EST 2013
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log\Provider;

use CodeRage\Log\Entry;

/**
 * @ignore
 */

/**
 * Implementation of CodeRage\Log\Provider that writes to a file pointer resource
 */
abstract class FileHandle implements \CodeRage\Log\Provider {

    /**
     * Constructs an instance of CodeRage\Log\Provider\FileHandle
     *
     * @param array $options The options array; supports the following options:
     *   file - The file handle
     *   format - A bitwise OR of zero or more of the constants
     *     CodeRage\Log\Entry::XXX
     */
    public function __construct($options)
    {
        if (!isset($options['file']))
            throw new
                \CodeRage\Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing file pointer'
                ]);
        $this->file = $options['file'];
        $this->format = isset($options['format']) ?
            $options['format'] :
            Entry::ALL;
    }

    public function __destruct() { @fclose($this->file); }

    /**
     * Delivers the given log entry
     *
     * @param CodeRage\Log\Entry $entry The log entry
     */
    public function dispatchEntry(Entry $entry)
    {
        fwrite($this->file, $entry->formatEntry($this->format));
    }

    /**
     * The file pointer
     *
     * @var resource
     */
    private $file;

    /**
     * A bitwise OR of zero or more of the constants CodeRage\Log\Entry::XXX
     *
     * @var int
     */
    private $format;
}
