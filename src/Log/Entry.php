<?php

/**
 * Defines the class CodeRage\Log\Entry
 *
 * File:        CodeRage/Log/Entry.php
 * Date:        Tue Jul 14 21:53:57 UTC 2015
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log;

use DateTime;
use Throwable;
use CodeRage\Error;
use CodeRage\Log;

/**
 * Represents a log entry
 */
final class Entry {

    /**
     * @var int
     */
    public const TIMESTAMP = 1;

    /**
     * @var int
     */
    public const SESSION_ID = 2;

    /**
     * @var int
     */
    public const TAGS = 4;

    /**
     * @var int
     */
    public const FILE_AND_LINE = 8;

    /**
     * @var int
     */
    public const EXCEPTION_DETAILS = 16;

    /**
     * @var int
     */
    public const DATA = 32;

    /**
     * @var int
     */
    public const NORMALIZE_SPACE = 64;

    /**
     * @var int
     */
    public const ALL = 63;

    /**
     * @var int
     */
    public const ALL_EXCEPT_SESSION = 61;

    /**
     * @var string
     */
    private const MATCH_TIMESTAMP =
        '/^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})[-+]\d{2}:\d{2}\] (.*)/';

    /**
     * @var string
     */
    private const MATCH_SESSION_ID = '/^([a-zA-Z0-9]{12}) (.*)/';

    /**
     * @var string
     */
    private const MATCH_TAGS = '/^(?:((?:\[[^]]+?\])*) )?(.*)/';

    /**
     * @var string
     */
    private const MATCH_FILE_AND_LINE = '/^([^:]+):(\d+|\?) (.*)/';

    /**
     * @var string
     */
    private const MATCH_LEVEL = '/^(?:(CRITICAL|ERROR|WARNING): )?(.*)/';

    /**
     * Constructs an instance of CodeRage\Log\Entry
     *
     * @param string $sessionId The session ID used to link log entries created
     *   by different processes
     * @param array $tags The list of tags
     * @param DateTime $timestamp The timestamp
     * @param int $level One of the constants CodeRage\Log::XXX
     * @param mixed $message The log message or instance of Exception
     * @param string $file The file pathname, if any
     * @param int $line The line number, if any
     * @param array $data Additional data associated with the message, if any;
     *   each value must be convertible to a string
     */
    public function __construct($sessionId, $tags, $timestamp, $level, $message,
            $file, $line, $data)
    {
        $this->sessionId = $sessionId;
        $this->tags = $tags;
        $this->timestamp = $timestamp;
        $this->level = $level;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->data = $data;
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
     * Returns the list of tags
     *
     * @return array
     */
    public function tags() : array
    {
        return $this->tags;
    }

    /**
     * Returns the timestamp
     *
     * @return DateTime
     */
    public function timestamp() : DateTime
    {
        return $this->timestamp;
    }

    /**
     * Returns one of the constants CodeRage\Log::XXX
     *
     * @return int
     */
    public function level() : int
    {
        return $this->level;
    }

    /**
     * Returns the log message or instance of Exception
     *
     * @return mixed
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * Returns the file pathname
     *
     * @return string
     */
    public function file() : ?string
    {
        return $this->file;
    }

    /**
     * Returns the line number
     *
     * @return int
     */
    public function line() : ?string
    {
        return $this->line;
    }

    /**
     * Returns additional data associated with the message, if any
     *
     * @return array
     */
    public function data() : ?array
    {
        return $this->data;
    }

    /**
     * Formats this entry based on the the given format flags
     *
     * @param int $format A bitwise OR of zero or more of the constants
     *   CodeRage\Log\Entry::XXX
     * @return string
     */
    public function formatEntry(int $format) : string
    {
        $line = '';
        if (($format & Entry::TIMESTAMP) != 0)
            $line .= '[' . self::formatTimestamp($this->timestamp) . '] ';
        if (($format & Entry::SESSION_ID) != 0)
            $line .= $this->sessionId . ' ';
        if (($format & Entry::TAGS) != 0 && count($this->tags) > 0)
            $line .= '[' . join('][', $this->tags) . '] ';
        if (($format & Entry::FILE_AND_LINE) != 0) {
            $line .= $this->file !== null ?
            $this->file :
            '?';
            $line .= ':';
            $line .= $this->line !== null ?
            $this->line :
            '?';
            $line .= ' ';
        }
        if ($this->level <= Log::WARNING)
            $line .= Log::translateLevel($this->level) . ': ';
        $line .= self::formatMessage($this->message, $format);
        if (($format & Entry::DATA) != 0 && !empty($this->data)) {
            $line .= "  Additional information:\n";
            foreach ($this->data as $n => $v) {
                $v = self::normalizeSpace($v);
                $line .= "  $n = $v\n";
            }
        }
        return $line;
    }

    /**
     * Formats the given timestamp according to ISO 8601
     *
     * @param DateTime $timestamp
     * @return string
     */
    public static function formatTimestamp(DateTime $timestamp) : string
    {
        return $timestamp->format(DATE_W3C);
    }

    /**
     * Formats the given log message
     *
     * @param mixed The log message or instance of Exception
     * @param int $format A bitwise or of zero more of the constants
     *   CodeRage\Log\Entry::XXX
     * @return string
     */
    public static function formatMessage(string $message, int $format = 0) : string
    {
        $text = !$message instanceof \Exception ?
            rtrim($message) . "\n" :
            ( ($format & Entry::EXCEPTION_DETAILS) != 0 ?
                    "\n" . Error::encode($message, true) :
                    rtrim($message->getMessage()) . "\n" );
        if (($format & Entry::NORMALIZE_SPACE) != 0)
            $text = self::normalizeSpace($text);
        return $text;
    }

    /**
     * Parses the given formatted log entry
     *
     * @param string $entry The formatted entry without exception information
     *   or additional data
     * @param int $format A bitwise of zero or more of the constants
     *   CodeRage\Log\Entry::XXX
     * @return CodeRage\Log\Entry
     */
    public static function parseEntry(string $entry, int $format) : self
    {
        $timestamp = $sessionId = $tags = $file = $line =
        $level = $message = $match = null;
        if (($format & Entry::TIMESTAMP) != 0) {
            if (preg_match(self::MATCH_TIMESTAMP, $entry, $match)) {
                $timestamp = new DateTime($match[1]);
                $entry = $match[2];
            } else {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => 'Missing timestamp'
                    ]);
            }
        }
        if (($format & Entry::SESSION_ID) != 0) {
            if (preg_match(self::MATCH_SESSION_ID, $entry, $match)) {
                $sessionId = $match[1];
                $entry = $match[2];
            } else {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => 'Missing session ID'
                    ]);
            }
        }
        if (preg_match(self::MATCH_TAGS, $entry, $match)) {
            $length = strlen($match[1]);
            $tags = $length > 0 ?
            explode('][', substr($match[1], 1, $length - 2)) :
            [];
            $entry = $match[2];
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Missing file and line'
                ]);
        }
        if (($format & Entry::FILE_AND_LINE) != 0) {
            if (preg_match(self::MATCH_FILE_AND_LINE, $entry, $match)) {
                $file = $match[1];
                $line = $match[2];
                $entry = $match[3];
            } else {
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => 'Missing file and line'
                    ]);
            }
        }
        if (preg_match(self::MATCH_LEVEL, $entry, $match)) {
            $level = strlen($match[1]) > 0 ?
            Log::translateLevel($match[1]) :
            Log::INFO;
            $entry = $match[2];
        } else {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'Missing file and line'
                ]);
        }
        $message = $entry;
        return new
        Entry(
                $sessionId,
                $tags,
                $timestamp,
                $level,
                $message,
                $file,
                $line,
                null
        );
    }

    /**
     * Formats the given stack trace
     *
     * @param array $stackTrace A structure in the format returned by
     *   debug_backtrace()
     * @param string $indent Text to prepend to each line
     * @return string
     */
    private static function formatStackTrace(array $stackTrace,
        string $indent = '') : string
    {
        $result = '';
        for ($z = 0, $n = count($stackTrace); $z < $n; ++$z) {
            $frame = $stackTrace[$z];
            $result .=
            $indent . '#' . ($z + 1) . ' ' .
            (!empty($frame['class']) ? "{$frame['class']}::" : '') .
            (!empty($frame['function']) ?
                    "{$frame['function']}()" :
                    'code' ) .
                    (!empty($frame['line']) ?
                            " called at line {$frame['line']}" :
                            "" ) .
                            (!empty($frame['file']) ?
                                    " in {$frame['file']}" :
                                    '');
            $result .= "\n";
        }
        return $result;
    }

    /**
     * Returns the result of trimming the given string and replacing replacing
     * newline characters with escape sequences
     *
     * @return string
     */
    private static function normalizeSpace(string $value) : string
    {
        return
        str_replace(
                ["\r\n", "\r", "\n", "\t"],
                ['\n', '\n', '\n', '\t'],
                trim($value)
        );
    }

    /**
     * The session ID used to link log entries created by different processes
     *
     * @var string
     */
    private $sessionId;

    /**
     * The list of tags
     *
     * @var array
     */
    private $tags;

    /**
     * The timestamp
     *
     * @var DateTime
     */
    private $timestamp;

    /**
     * One of the constants CodeRage\Log::XXX
     *
     * @var int
     */
    private $level;

    /**
     * The log message or instance of Exception
     *
     * @var string
     */
    private $message;

    /**
     * The file pathname
     *
     * @var string
     */
    private $file;

    /**
     * The line number
     *
     * @var int
     */
    private $line;

    /**
     * Additional data associated with the message, if any
     *
     * @var array
     */
    private $data;
}
