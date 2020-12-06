<?php

/**
 * Defines the class CodeRage\Log\Impl
 *
 * File:        CodeRage/Log/Impl.php
 * Date:        Tue Jul 14 21:53:57 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log;

use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Util\Random;


/**
 * Implements CodeRage\Log
 */
final class Impl {

    /**
     * Constructs an instance of CodeRage\Log\Impl
     */
    public function __construct()
    {
        static $sessionId;
        if ($sessionId === null)
            $sessionId = Random::string(Log::SESSION_ID_LENGTH);
        $this->sessionId = $sessionId;
        for ($z = Log::CRITICAL; $z <= Log::DEBUG; ++$z)
            $this->streams[$z] = null;
    }

    /**
     * Returns the stream, if any, for writing log entries of the given level
     *
     * @param int $level One of the constants CodeRage\Log::XXX
     */
    public function getStream(int $level) : ?Stream
    {
        return $this->streams[$level];
    }

    /**
     * Registers a log provider
     *
     * @param CodeRage\Log\Provider $provider The provider
     * @param int $level One of the constants CodeRage\Log::XXX
     */
    public function registerProvider(Provider $provider, int $level) : void
    {
        $this->providers[] = [$provider, $level];
        while ($level >= Log::CRITICAL && $this->streams[$level] === null) {
            $this->streams[$level] = new Stream($level, $this);
            --$level;
        }
    }

    /**
     * Unregisters a log provider
     *
     * @param CodeRage\Log\Provider $provider The provider
     */
    public function unregisterProvider(Provider $provider) : void
    {
        for ($i = 0, $n = count($this->providers); $i < $n; ++$i) {
            if ($this->providers[$i][0] === $provider) {
                array_splice($this->providers, $i, 1);
                break;
            }
        }
    }

    /**
     * Returns the session ID used to link log entries created by different
     * processes
     *
     * @return string
     */
    public function sessionId() : string
    {
        return $this->sessionId;
    }

    /**
     * Sets the session ID used to link log entries created by different
     * processes
     *
     * @param string $sessionId The session ID
     */
    public function setSessionId(string $sessionId) : void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Returns the list of tags
     *
     * @return array
     */
    public function tags() : array
    {
        return $this->tags;
    }

    /**
     * Sets a text label to be associated with every entry written to the
     * log
     *
     * @param string $tag The tag; may contain any printable characters except
     *   square brackets
     */
    public function setTag(string $tag) : void
    {
        if (strlen($tag) == 0) {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Empty log tag'
                ]);
        }
        if ( strpos($tag, '[') !== false ||
             strpos($tag, ']') !== false ||
             preg_match('/[^[:print:]]$/', $tag) )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Invalid log tag: $tag"
                ]);
        }
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
            sort($this->tags);
        }
    }

    /**
     * Removes the given tag
     *
     * @param string $tag The tag
     */
    public function clearTag(string $tag) : void
    {
        if (($pos = array_search($tag, $this->tags)) !== null)
            array_splice($this->tags, $pos, 1);
    }

    /**
     * Creates a log entry
     *
     * @param int $level One of the constants CodeRage\Log::XXX
     * @param string $message The log message
     * @param array $data Additional data associated with the message, if any;
     *   each value must be convertible to a string
     * @param int $depth The number of function calls separating the call to
     *   this method and the logging context
     */
    public function write($level, string $message, ?array $data, int $depth) : void
    {
        if ($message instanceof \Throwable) {
            for ($e = $message; $e !== null; $e = $e->getPrevious()) {
                if ( ( $e instanceof \Error ) ||
                     ( $e instanceof \CodeRage\Error &&
                           ( $e->status() == 'DATABASE_ERROR' ||
                             $e->status() == 'PHP_ERROR' ) ) )
                {
                    $level = Log::CRITICAL;
                }
            }
        }
        $timestamp = (new \DateTime)->setTimestamp(\CodeRage\Util\Time::real());
        $trace = debug_backtrace();
        $file = isset($trace[$depth]['file']) ?
            $trace[$depth]['file'] :
            null;
        $line = isset($trace[$depth]['line']) ?
            $trace[$depth]['line'] :
            null;
        $entry =
            new Entry(
                    $this->sessionId,
                    $this->tags,
                    $timestamp,
                    $level,
                    $message,
                    $file,
                    $line,
                    $data
                );
        for ($z = count($this->providers) - 1; $z >= 0; --$z) {
            list ($p, $l) = $this->providers[$z];
            if ($l < $level)
                continue;
            try {
                $p->dispatchEntry($entry);
            } catch (\Throwable $e) {
                array_splice($this->providers, $z, 1);
                $this->write(
                    Log::ERROR,
                    "Log provider " . $p->name() . " failed: $e",
                    null,
                    0
                );
            }
        }
    }

    /**
     * The session ID used to link log entries created by different processes
     *
     * @var string
     */
    private $sessionId;

    /**
     * A list of text labels to be associated with every entry written to the
     * log
     *
     * @var array
     */
    private $tags = [];

    /**
     * Associative array mapping integral log levels to instances of
     * CodeRage\Log\Stream
     *
     * @var array
    */
    private $streams = [];

    /**
     * List of pair ($provider, $level)
     *
     * @var array
    */
    private $providers = [];
}
