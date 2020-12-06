<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\Syslog
 *
 * File:        CodeRage/Log/Provider/Syslog.php
 * Date:        date
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log\Provider;

use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Text\Regex;
use CodeRage\Util\Args;
use CodeRage\Util\Json;

/**
 * @ignore
 */

/**
 * Implementation of CodeRage\Log\Provider that writes to syslog
 */
final class Syslog implements \CodeRage\Log\Provider {

    /**
     * @var array
     */
    private const OPTIONS =
        [ 'ident' => 1, 'options' => 1, 'facility' => 1, 'exclude' => 1,
          'custom' => 1, 'includeUrl' => 1 ];

    /**
     * @var array
     */
    private const FIELDS =
        [ 'level' => 1, 'message' => 1, 'tags' => 1, 'timestamp' => 1,
          'file' => 1, 'line' => 1, 'data' => 1, 'sessionId' => 1 ];

    /**
     * @var array
     */
    private const RESERVED_FIELDS = ['url' => 1];

    /**
     * @var string
     */
    private const DEFAULT_IDENT = 'coderage';

    /**
     * @var string
     */
    private const DEFAULT_FACILITY = 'LOG_USER';

    /**
     * @var array
     */
    private const OPENLOG_OPTIONS =
        [ 'LOG_CONS' => 1, 'LOG_NDELAY' => 1, 'LOG_ODELAY' => 1,
          'LOG_PERROR' => 1, 'LOG_PID' => 1 ];


    private const OPENLOG_FACILTIES =
        [ 'LOG_AUTH' => 1, 'LOG_AUTHPRIV' => 1, 'LOG_CRON' => 1,
          'LOG_DAEMON' => 1, 'LOG_KERN' => 1, 'LOG_LOCAL0' => 1,
          'LOG_LOCAL1' => 1, 'LOG_LOCAL2' => 1, 'LOG_LOCAL3' => 1,
          'LOG_LOCAL4' => 1, 'LOG_LOCAL5' => 1, 'LOG_LOCAL6' => 1,
          'LOG_LOCAL7' => 1, 'LOG_LPR' => 1, 'LOG_MAIL' => 1, 'LOG_NEWS' => 1,
          'LOG_SYSLOG' => 1, 'LOG_USER' => 1, 'LOG_UUCP' => 1 ];

    /**
     * @var string
     */
    private const MATCH_FIELD = '#^\s*([^:]+?)\s*:\s*(.+?)\s*$#';

    /**
     * Constructs an instance of CodeRage\Log\Provider\PrivateFile
     *
     * @param array $options The options array; supports the following options:
     *   ident - The $ident argument to openlog() (optional)
     *   options - The $options argument to openlog(), as a comma-separated list
     *     of strings of the form LOG_XXX (optional)
     *   facility - The facility argument to openlog(), as a string of the form
     *     LOG_XXX
     *   exclude - A comma-separated list of strings among 'sessionId', 'tags',
     *     'timestamp', 'level', 'message', 'file', 'line', and 'data'
     *     (optional)
     *   custom - A list of custom fields to include in each entry, specified as
     *     a CSV-formatted list of values of the form "name:value"; e.g.,
     *     "application: MetalMG, version: 6.5.3"
     *   includeUrl - true to include a "url" field linking to the log session
     *     in each entry
     */
    public function __construct(array $options = [])
    {
        foreach (array_keys($options) as $n)
            if (!array_key_exists($n, self::OPTIONS))
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Unsupported option: $n"
                    ]);
        Args::checkKey($options, 'ident', 'string', [
            'default' => self::DEFAULT_IDENT
        ]);
        $this->processFlags($options);
        $this->processFacility($options);
        $this->processExclude($options);
        $this->processCustom($options);
        Args::checkBooleanKey($options, 'includeUrl', ['default' => false]);
        $this->ident = $options['ident'];
        $this->options = $options['options'];
        $this->facility = $options['facility'];
        $this->exclude = $options['exclude'];
        $this->custom = $options['custom'];
        $this->includeUrl = $options['includeUrl'];
        $this->open = false;
    }

    public function name() { return 'syslog'; }

    public function dispatchEntry(\CodeRage\Log\Entry $entry)
    {
        $fields = [];
        foreach (array_keys(self::FIELDS) as $n) {
            if (isset($this->exclude[$n]))
                continue;
            $v = $entry->$n();
            if ($v === null)
                continue;
            switch ($n) {
            case 'timestamp':
                $fields[$n] = $v->format(DATE_W3C);
                break;
            case 'level':
                $fields[$n] = Log::translateLevel($v);
                break;
            case 'tags':
            case 'data':
                if (!empty($v))
                    $fields[$n] = $v;
                break;
            default:
                $fields[$n] = $v;
                break;
            }
        }
        if ($this->includeUrl)
            $fields['url'] =
                \CodeRage\Log\Provider\Db::url($entry->sessionId());
        $fields += $this->custom;
        $json = json_encode($fields);
        if ($json === false)
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => 'JSON encoding error: ' . Json::lastError()
                ]);
        if (!$this->open) {
            openlog($this->ident, $this->options, $this->facility);
            $this->open = true;
        }
        syslog(self::translateLevel($entry->level()), $json);
    }

    /**
     * Processes the 'options' constructor option
     *
     * @param array $options The options array passed to the constructor
     */
    private function processFlags(array &$options) : void
    {
        $opts = Args::checkKey($options, 'options', 'string');
        if ($opts !== null) {
            $opts = trim($opts);
            if (strlen($opts) == 0)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Empty 'options' option"
                        ]);
            $flags = 0;
            foreach (preg_split('/\s*,\s*/', $opts) as $flag) {
                if ( !array_key_exists($flag, self::OPENLOG_OPTIONS) ||
                     !defined($flag) )
                {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Invalid 'options' option '$opts': " .
                                "unsupported flag '$flag'"
                        ]);
                }
                if (($flags & constant($flag)) !== 0)
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Invalid 'options' option '$opts': " .
                                "duplicate flag '$flag"
                        ]);
                $flags |= constant($flag);
            }
            $options['options'] = $flags;
        } else {
            $options['options'] = 0;
        }
    }

    /**
     * Processes the 'facility' constructor option
     *
     * @param array $options The options array passed to the constructor
     */
    private function processFacility(array &$options) : void
    {
        $facility =
            Args::checkKey($options, 'facility', 'string', [
                'default' => self::DEFAULT_FACILITY
            ]);
        if ( !array_key_exists($facility, self::OPENLOG_FACILTIES) ||
             !defined($facility) )
        {
            throw new
                Error([
                    'status' => 'INVALID_PARAMETER',
                    'details' => "Unsupported facility: $facility"
                ]);
        }
        $options['facility'] = constant($facility);
    }

    /**
     * Processes the 'exclude' constructor option
     *
     * @param array $options The options array passed to the constructor
     */
    private function processExclude(array &$options) : void
    {
        $exclude = Args::checkKey($options, 'exclude', 'string');
        if ($exclude !== null) {
            $exclude = trim($exclude);
            if (strlen($exclude) == 0)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'details' => "Empty 'exclude' option"
                        ]);
            $fields = [];
            foreach (preg_split('/\s*,\s*/', $exclude) as $n) {
                if (!array_key_exists($n, self::FIELDS))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Invalid 'exclude' option '$exclude': " .
                                "unsupported field '$n"
                        ]);
                if (isset($fields[$n]))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'details' =>
                                "Invalid 'exclude' option '$exclude': " .
                                "duplicate field '$n"
                        ]);
                $fields[$n] = 1;
            }
            $options['exclude'] = $fields;
        } else {
            $options['exclude'] = [];
        }
    }

    /**
     * Processes the 'custom' constructor option
     *
     * @param array $options The options array passed to the constructor
     */
    private function processCustom(array &$options) : void
    {
        $custom = Args::checkKey($options, 'custom', 'string');
        if ($custom !== null) {
            $mem = fopen('php://memory', 'rw');
            fwrite($mem, $custom);
            rewind($mem);
            $csv = fgetcsv($mem);
            if ($csv === false)
                throw new
                    Error([
                        'status' => 'INVALID_PARAMETER',
                        'detail' => "Invalid 'custom' option: $csv"
                    ]);
            $fields = [];
            foreach ($csv as $field) {
                [$_, $n, $v] =
                    Regex::getMatch(self::MATCH_FIELD, $field, Regex::FORCE);
                if ($_ === null)
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'detail' => "Invalid custom field: $field"
                        ]);
                if (array_key_exists($n, self::FIELDS))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'detail' =>
                                "Invalid custom field $field: the name '$n' " .
                                "is reserved"
                        ]);
                if (array_key_exists($n, self::RESERVED_FIELDS))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'detail' =>
                                "Invalid custom field $field: the name '$n' " .
                                "is reserved"
                        ]);
                if (isset($fields[$n]))
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'detail' => "Duplicate custom field: $n"
                        ]);
                $fields[$n] = $v;
            }
            $options['custom'] = $fields;
        } else {
            $options['custom'] = [];
        }
    }

    /**
     * Translate CodeRage\Log constants representing log levels to LOG_XXX
     * constants
     *
     * @param int $level
     * @return int
     */
    private static function translateLevel(int $level) : int
    {
        switch ($level) {
        case Log::CRITICAL:  return LOG_CRIT;
        case Log::ERROR:     return LOG_ERR;
        case Log::WARNING:   return LOG_WARNING;
        case Log::INFO:      return LOG_INFO;
        case Log::VERBOSE:
        case Log::DEBUG:
        default:
            return LOG_DEBUG;
        }
    }

    /**
     * The $ident argument to openlog
     *
     * @var string
     */
    private $ident;

    /**
     * The $options argument to openlog
     *
     * @var string
     */
    private $options;

    /**
     * The $facility argument to openlog
     *
     * @var string
     */
    private $facility;


    /**
     * Associative array with keys among 'sessionId', 'tags', 'timestamp',
     * 'level', 'message', 'file', 'line', and 'data'
     *
     * @var array
     */
    private $exclude;

    /**
     * Custom fields and values to be included in each entry
     *
     * @var array
     */
    private $custom;

    /**
     * true to include a "url" field linking to the log session in each entry
     *
     * @var boolean
     */
    private $includeUrl;

    /**
     * true if openlog() has been called
     *
     * @var boolean
     */
    private $open;
}
