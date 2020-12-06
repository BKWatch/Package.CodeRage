<?php

/**
 * Contains the definition of the class CodeRage\Log\Provider\Smtp
 *
 * File:        CodeRage/Log/Provider/Smtp.php
 * Date:        Thu Jan 31 20:33:13 EST 2013
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Log\Provider;

use CodeRage\Config;
use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Log\Entry;
use CodeRage\Text;
use CodeRage\Util\Args;

/**
 * @ignore
 */

/**
 * Implementation of CodeRage\Log\Provider that sends entries via SMTP
 */
final class Smtp implements \CodeRage\Log\Provider {

    /**
     * @var int
     */
    const DEFAULT_MAX_PER_SESSION = 1;

    /**
     * Constructs an instance of CodeRage\Log\Provider\Smtp
     *
     * @param array $options The options array; supports the following options:
     *   engine - The name of a class implementing the interface
     *     CodeRage.Util.SmtpEngine, in dot-separated format (optional)
     *   params - An associative array of string-valued properties, encoded as
     *     JSON; defaults to an empty object
     *   from - A comma-separated list of recipient addresses
     *   to - The recipient address
     *   ignore - A comma-separated list of status codes of exceptions that
     *     should not be logged (optional)
     */
    function __construct($options)
    {
        Args::checkKey($options, 'engine', 'string', [
            'label' => 'SMTP engine class name',
            'default' => null
        ]);
        Args::checkKey($options, 'params', 'string', [
            'label' => 'SMTP engine parameters',
            'default' => null
        ]);
        if (isset($options['params'])) {
            $handler = new \CodeRage\Util\ErrorHandler;
            $result = $handler->_json_decode($options['params']);
            $error = 'Failed decoding SMTP engine parameters';
            if ($handler->errno())
                throw new Error(['message' => $handler->formatError($error)]);
            if ($result === null && trim($options['params']) != 'null')
                throw new Error(['message' => $error]);
            Args::check($result, 'stdClass', 'SMTP engine parameters');
            $options['params'] = (array) $result;
        }
        Args::checkKey($options, 'from', 'string', [
            'label' => 'sender address'
        ]);
        Args::checkKey($options, 'to', 'string', [
            'label' => 'receipient addresses'
        ]);
        Args::checkKey($options, 'ignore', 'string', [
            'label' => "'ignore' option"
        ]);
        if (isset($options['ignore'])) {
            $codes = [];
            foreach (Text::split($options['ignore'], '/\s*,\s*/') as $code)
                $codes[$code] = 1;
            $options['ignore'] = $codes;
        }

        // Note: CodeRage\Log\Provider\Smtp is used by the build system before
        // a runtime configuration has been generated and the file CodeRage.php
        // included; therefore we must not assume that the class CodeRage\Config
        // is available
        $config = class_exists('CodeRage\Config') ?
            Config::current() :
            null;
        if (!isset($options['to'])) {
            if ($config === null || !$config->hasProperty('error_email'))
                throw new
                    Error([
                        'status' => 'MISSING_PARAMETER',
                        'message' => 'Missing email recipient'
                    ]);
            $options['to'] = $config->getRequiredProperty('error_email');
        }
        if (!isset($options['from'])) {
            $domain = $config !== null ?
                $config->getProperty('site_domain', 'localhost.localdomain') :
                'localhost.localdomain';
            $options['from'] = "no-reply@$domain";
        }

        $this->options = $options;
    }

    function name() { return 'smtp'; }

    /**
     * Delivers the given log entry
     *
     * @param CodeRage\Log\Entry $entry The log entry
     */
    public function dispatchEntry(Entry $entry)
    {
        // Check whether message should be ignored
        if ($this->ignoreError($entry))
            return;
        if (!isset($this->options['minLevel']))
            $this->options['minLevel'] = Log::INFO;
        $level = $entry->level();
        if ($level >= $this->options['minLevel'])
            return;
        $this->options['minLevel'] = $level;

        // Construct subject and date headers
        $timestamp = $entry::formatTimestamp($entry->timestamp());
        $config = class_exists('CodeRage\\Config') ?
            Config::current() :
            null;
        $subject = $config !== null && $config->hasProperty('project.label') ?
            '[' . $config->getProperty('project.label') . '] ' :
            '';
        foreach ($entry->tags() as $t)
            $subject .= "[$t]";
        $subject .= ' ';
        switch ($level) {
        case Log::CRITICAL:
            $subject .= 'Critical';
            break;
        case Log::ERROR:
            $subject .= 'Error';
            break;
        case Log::WARNING:
            $subject .= 'Warning';
            break;
        default:
            $subject .=
                'Message (Level: ' . Log::translateLevel($level) . ')';
            break;
        }
        $subject .= " ($timestamp)";

        // Construct message body
        $file = $entry->file() !== null ? $entry->file() : '?';
        $line = $entry->line() !== null ? $entry->line() : '?';
        $sessionId = $entry->sessionId();
        $tags = join(', ', $entry->tags());
        $dbLink = null;
        if ($config !== null) {
            $url = \CodeRage\Log\Provider\Db::url($sessionId);
            $dbLink =
                "\nIf database logging is enabled, you may view this log " .
                "session at $url";
        }
        $format = Entry::ALL;
        $body =
            "Time:     $timestamp\n" .
            "Level:    " . Log::translateLevel($level) . "\n" .
            "File:     $file\n" .
            "Line:     $line\n" .
            "Session:  $sessionId\n" .
            "Tags:     $tags\n\n" .
            Entry::formatMessage($entry->message(), $format) . "\n" .
            $dbLink;

        // Send message
        $this->smtp()->send([
            'from' => $this->options['from'],
            'to' => $this->options['to'],
            'subject' => $subject,
            'text' => $body
        ]);
    }

    /**
     * Returns true if the given entry contains an instance of CodeRage\Error with
     * one of the status codes appearing in the configuration option
     * 'ignore'
     *
     * @param CodeRage\Log\Entry $entry The log entry
     */
    public function ignoreError(Entry $entry)
    {
        return $entry->message() instanceof Error &&
               isset($this->options['ignore']) &&
               isset($this->options['ignore'][$entry->message()->status()]);
    }

    /**
     * Create and returns an instance of \CodeRage\Util\Smtp
     *
     * @return \CodeRage\Util\Smtp
     */
    private function smtp()
    {
        if ($this->smtp === null)
            $this->smtp =
                new \CodeRage\Util\Smtp([
                        'engine' => $this->options['engine'],
                        'params' => $this->options['params'],
                    ]);
        return $this->smtp;
    }

    /**
     * The configuration options
     *
     * @var array
     */
    private $options;

    /**
     * The instance of CodeRage\Util\Smtp used to send email
     *
     * @var CodeRage\Util\Smtp
     */
    private $smtp;
}
