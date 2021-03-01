<?php

/**
 * Defines the class CodeRage\Sys\Command\Base
 *
 * File:        CodeRage/Sys/Command/Base.php
 * Date:        Thu Jan 24 13:39:40 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Command;

use CodeRage\Sys\Engine;
use CodeRage\Error;
use CodeRage\Log;
use CodeRage\Util\Args;

/**
 * Base class from crush subcommands
 */
class Base extends \CodeRage\Util\CommandLine {

    /**
     * The default log level for console logging.
     *
     * @var int
     */
    private const LOG_CONSOLE_LEVEL = Log::INFO;

    /**
     * Value of the 'stream' parameter to the CodeRage\Log\Provider\Console
     * constructor
     *
     * @var int
     */
    private const LOG_CONSOLE_STREAM = 'stderr';

    /**
     * Value of the 'format' parameter to the CodeRage\Log\Provider\Console
     * constructor
     *
     * @var int
     */
    private const LOG_CONSOLE_FORMAT = 0;

    /**
     * The log level for entry counting.
     *
     * @var int
     */
    private const LOG_COUNTER_LEVEL = Log::WARNING;

    /**
     * The default log level for file logging.
     *
     * @var int
     */
    private const LOG_FILE_LEVEL = Log::INFO;

    /**
     * The default log level for file logging.
     *
     * @var int
     */
    private const LOG_FILE_FORMAT = \CodeRage\Log\Entry::ALL_EXCEPT_SESSION;

    /**
     * The log level for SMTP logging.
     *
     * @var int
     */
    private const LOG_SMTP_LEVEL = Log::WARNING;

    /**
     * Constructs an instance of CodeRage\Sys\Command\Base
     *
     * @param array $options The options array; support all options supported by
     *   the CodeRage\Util\CommandLine constructor, plus the following options:
     *     defaultLogLevel - The default log level for console output
     */
    public function __construct(array $options)
    {
        $level =
            Args::checkKey($options, 'defaultLogLevel', 'int', [
                'unset' => true
            ]);
        parent::__construct($options);
        $this->defaultLogLevel = $level;
    }

    /**
     * Adds --set and --unset options
     *
     * @param \CodeRage\Util\CommandLine $cmd
     */
    final protected function addConfigOptions() : void
    {
        $this->addOption([
            'shortForm' => 's',
            'longForm' => 'set',
            'description' =>
                'assigns the configuration variable NAME the value VALUE',
            'placeholder' => 'NAME=VALUE',
            'multiple' => 1
        ]);
        $this->addOption([
            'shortForm' => 'u',
            'longForm' => 'unset',
            'description' =>
                'unsets the configuration variable NAME previously ' .
                'specified on the command line',
            'placeholder' => 'NAME',
            'multiple' => 1
        ]);
    }

    /**
     * Returns an associative array of configuration variables set on the given
     * command line using the option --set
     *
     * @param CodeRage\Util\CommandLine $cmd
     * @return array
     */
    final protected function setProperties() : array
    {
        $properties = []; new \CodeRage\Sys\Config\Basic;
        $pattern = '/^([_a-z][_a-z0-9]*(?:\.[_a-z][_a-z0-9]*)*)=(.*)$/i';
        if ($values = $this->getValue('s', true)) {
            foreach ($values as $v) {
                if (preg_match($pattern, $v, $match)) {
                    $properties[$match[1]] = $match[2];
                } else {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' => "Invalid option: --set $v"
                        ]);
                }
            }
        }
        return $properties;
    }

    /**
     * Returns a list of the names of configuration variables unset on the given
     * command line using the option --unset
     *
     * @param CodeRage\Util\CommandLine $cmd
     * @return array
     */
    final protected function unsetProperties() : array
    {
        $properties = [];
        if ($values = $this->getValue('u', true)) {
            $pattern = '/^[_a-z][_a-z0-9]*(?:\.[_a-z][_a-z0-9]*)*$/i';
            foreach ($values as $v) {
                if (preg_match($pattern, $v)) {
                    $properties[] = $v;
                } else {
                    throw new
                        Error([
                            'status' => 'INVALID_PARAMETER',
                            'message' => "Invalid option: --unset $v"
                        ]);
                }
            }
        }
        return $properties;
    }

    /**
     * Constructs a build engine with a log based on command-line options
     * @return CodeRage\Sys\Engine
     *
     * @param array $options The options array; supports the following options:
     *     defaultLogLevel - The default log level for console output
     */
    protected function createEngine(array $options = []) : Engine
    {
        $log = new Log;
        $log->setTag('Build');

        // Configure provider 'console'
        $parent = $this->parent();
        $level = null;
        if ($parent->getValue('quiet')) {
            if ($parent->hasValue('log-level-console')) {
                $value = $parent->getValue('log-level-console');
                if (Log::translateLevel($value) != Log::ERROR)
                    throw new
                        Error(['message' =>
                            "The option --quiet is incomatible with the " .
                            "option --log-level-console $value"
                        ]);
            }
            $level = Log::ERROR;
        } elseif ($parent->hasValue('log-level-console')) {
            $level =
                Log::translateLevel
                    ($parent->getValue('log-level-console')
                );
        } else {
            $level =
                Args::checkKey($options, 'defaultLogLevel', 'int', [
                    'default' =>
                        $this->defaultLogLevel ?? self::LOG_CONSOLE_LEVEL
                ]);
        }
        $console =
            new \CodeRage\Log\Provider\Console([
                    'stream' => self::LOG_CONSOLE_STREAM,
                    'format' => self::LOG_CONSOLE_FORMAT
                ]);
        $log->registerProvider($console, $level);

        // Configure provider 'counter'
        $counter = new \CodeRage\Log\Provider\Counter;
        $log->registerProvider($counter, self::LOG_COUNTER_LEVEL);

        // Configure provider 'file'
        $file = $parent->getValue('log');
        if ($file !== null) {
            $path = File::isAbsolute($file) ?
                $file :
                Config::projectRoot() . '/' . $file;
            $file =
                new \CodeRage\Log\Provider\PrivateFile([
                        'path' => $file,
                        'format' => self::LOG_FILE_FORMAT
                    ]);
            $level = $parent->getValue('log-level') !== null ?
                Log::translateLevel($parent->getValue('log-level')) :
                self::LOG_FILE_LEVEL;
            $log->registerProvider($file, $level);
        }

        return new Engine(['log' => $log]);
    }

    /**
     * @var string
     */
    private $defaultLogLevel;
}
