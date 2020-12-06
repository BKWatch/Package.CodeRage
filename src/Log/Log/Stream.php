<?php

/**
 * Defines the class CodeRage\Log\Stream
 *
 * File:        CodeRage/Log/Stream.php
 * Date:        Tue Jul 14 21:53:57 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log;

/**
 * Writes entries to a log
 */
class Stream {

    /**
     * Constructs an instance of CodeRage\Log\Stream
     *
     * @param int $level One of the constants CodeRage\Log::XXX
     * @param CodeRage\Log\Impl $impl
     */
    public function __construct($level, $impl)
    {
        $this->level = $level;
        $this->impl = $impl;
    }

    /**
     * Writes an entry to the log
     *
     * @param mixed $message The log message or instance of Throwable
     * @param array $data Additional data associated with the message, if any;
     *   each value must be convertible to a string
     */
    function write($message, $data = [])
    {
        $this->impl->write($this->level, $message, $data, 1);
    }

    /**
     * One of the constants CodeRage\Log::XXX
     *
     * @var int
     */
    private $level;

    /**
     * @var CodeRage\Log\Impl
     */
    private $impl;
}
