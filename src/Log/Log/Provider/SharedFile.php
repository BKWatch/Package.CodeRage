<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\SharedFile
 *
 * File:        CodeRage/Log/Provider/SharedFile.php
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
use CodeRage\File;
use CodeRage\Log\Entry;

/**
 * @ignore
 */

/**
 * Log provider that writes entries to files rooted at a specified directory
 */
final class SharedFile implements \CodeRage\Log\Provider {

    /**
     * Constructs an instance of CodeRage\Log\Provider\SharedFile
     *
     * @param array $options The options array; supports the following options:
     *   root - The root directory
     *   pathFormat - A format string to be passed to strftime
     *   entryFormat - A bitwise OR of zero or more of the constants
     *     CodeRage\Log\Entry:XXX
     */
    public function __construct($options)
    {
        if (!isset($options['root']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing root directory'
                ]);
        $this->root = $options['root'];
        if (!file_exists($this->root))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "The file '$this->root' doesn't exist"
                ]);
        if (!is_dir($this->root))
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'message' => "The file '$this->root' is not a directory"
                ]);
        if (!isset($options['pathFormat']))
            throw new
                Error([
                    'status' => 'MISSING_PARAMETER',
                    'message' => 'Missing path format string'
                ]);
        $this->path = $this->root . '/' . strftime($options['pathFormat']);
        $this->entryFormat = isset($options['entryFormat']) ?
            $options['entryFormat'] :
            Entry::ALL;
        File::mkdir(dirname($this->path));
    }

    /**
     * Returns "shared_file"
     *
     * @return string
     */
    function name() { return 'shared_file'; }

    /**
     * Delivers the given log entry
     *
     * @param CodeRage\Log\Entry $entry The log entry
     */
    function dispatchEntry(Entry $entry)
    {
        $handler = new \CodeRage\Util\ErrorHandler;
        $file = $handler->_fopen($this->path, 'a');
        if ($handler->errno())
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'message' =>
                        "Failed opening file '$this->path': " .
                        $handler->errstr()
                ]);
        $success = $handler->_flock($file, LOCK_EX);
        if (!$handler->_flock($file, LOCK_EX) || $handler->errno()) {
            @fclose($file);
            $message = "Failed acquiring lock on file '$this->path'";
            if ($handler->errno())
                $message .= ': ' . $handler->errstr();
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'message' => $message
                ]);
        }
        $result = $handler->_fseek($file, 0, SEEK_END);
        if ($result == -1 || $handler->errno()) {
            $message = "Failed seeking to end of file '$this->path'";
            if ($handler->errno())
                $message .= ': ' . $handler->errstr();
            @flock($file, LOCK_UN);
            @fclose($file);
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'message' => $message
                ]);
        }
        $success = $handler->_fwrite($file, $entry->formatEntry($this->entryFormat));
        if ($success === false || $handler->errno()) {
            $message = "Failed writing to file '$this->path'";
            if ($handler->errno())
                $message .= ': ' . $handler->errstr();
            @flock($file, LOCK_UN);
            @fclose($file);
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'message' => $message
                ]);
        }
        if (!$handler->_flock($file, LOCK_UN) || $handler->errno()) {
            @fclose($file);
            $message = "Failed releasing lock on file '$this->path'";
            if ($handler->errno())
                $message .= ': ' . $handler->errstr();
            throw new
                Error([
                    'status' => 'FILESYSTEM_ERROR',
                    'message' => $message
                ]);
        }
        @fclose($file);
    }

    /**
     * The file pathname
     *
     * @var string
     */
    private $path;

    /**
     * A bitwise OR of zero or more CodeRage\Log\Entry::XXX constants
     *
     * @var int
     */
    private $entryFormat;
}
