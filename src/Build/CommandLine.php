<?php

/**
 * Defines the class CodeRage\Build\CommandLine.
 *
 * File:        CodeRage/Build/CommandLine.php
 * Date:        Thu Jan 24 13:39:40 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\Error;
use CodeRage\File;
use CodeRage\Log;
use CodeRage\Text;

/**
 * Subclass of CodeRage\Util\CommandLine with the options used by CodeRage.Build.
 */
final class CommandLine extends \CodeRage\Util\CommandLine {

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
     * Returns a parsed command-line object.
     */
    public function __construct()
    {
        parent::__construct(
            'crush [options] [command [options]]',
            'The CodeRage command-line'
        );
        $this->addOption([
            'shortForm' => 'q',
            'longForm' => 'quiet',
            'description' =>
                'sets the log level for console output to WARNING',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'longForm' => 'log',
            'description' => 'specifies the location of the log file',
            'placeholder' => 'PATH'
        ]);
        $this->addOption([
            'longForm' => 'log-level',
            'description' =>
                'specifies the log level for the log file; ' .
                '<<LEVEL>> can be one of CRITICAL, ERROR, WARNING, INFO, ' .
                'VERBOSE, or DEBUG; defaults to INFO'
        ]);
        $this->addOption([
            'longForm' => 'log-level-console',
            'description' =>
                'specifies the log level for console output; ' .
                '<<LEVEL>> can be one of CRITICAL, ERROR, WARNING, INFO, ' .
                'VERBOSE, or DEBUG; defaults to INFO'
        ]);
        $build =
            new \CodeRage\Util\CommandLine([
                    'name' => 'build',
                    'description' => 'Builds a project',
                    'action' =>
                        function($cmd)
                        {
                            $set = self::setProperties($cmd);
                            $unset = self::unsetProperties($cmd);
                            return $this->createEngine()->build([
                                'setProperties' => $set,
                                'unsetProperties' => $unset
                            ]);
                        }
                ]);
        self::addConfigOptions($build);
        $this->addSubcommand($build);
        $this->addSubcommand([
            'name' => 'clean',
            'description' => 'Removes generated file',
            'action' =>
                function($cmd) { return $this->createEngine()->clean(); }
        ]);
        $config =
            new \CodeRage\Util\CommandLine([
                    'name' => 'config',
                    'description' => 'Sets configuration variables',
                    'action' =>
                        function($cmd)
                        {
                            $set = self::setProperties($cmd);
                            $unset = self::unsetProperties($cmd);
                            return $this->createEngine()->config([
                                'setProperties' => $set,
                                'unsetProperties' => $unset
                            ]);
                        }
                ]);
        self::addConfigOptions($config);
        $this->addSubcommand($config);
        $this->addSubcommand([
            'name' => 'info',
            'description' => 'Displays information about a project',
            'action' =>
                function($cmd)
                {
                    echo BuildParams::load();
                    return true;
                }
        ]);
        $this->addSubcommand([
            'name' => 'install',
            'description' => 'Installs or updates project components',
            'action' =>
                function($cmd) { return $this->createEngine()->install(); }
        ]);
        $this->addSubcommand([
            'name' => 'reset',
            'description' =>
                'Deletes generates files and clears configuration variables ' .
                'set on the command line',
            'action' => function($cmd) { $this->createEngine()->reset(); }
        ]);
        $this->addSubcommand([
            'name' => 'sync',
            'description' =>
                'Synchronizes the database with data in the code base',
            'action' =>
                function($cmd) { return $this->createEngine()->sync(); }
        ]);
    }

    /**
     * Constructs a build engine with a log based on command-line options
     * @return CodeRage\Build\Engine
     */
    private function createEngine() : Engine
    {
        $log = new Log;
        $log->setTag('Build');

        // Configure provider 'console'
        $level = null;
        if ($this->getValue('quiet')) {
            if ($this->hasValue('log-level-console')) {
                $value = $this->getValue('log-level-console');
                if (Log::translateLevel($value) != Log::ERROR)
                    throw new
                        Error(['message' =>
                            "The option --quiet is incomatible with the " .
                            "option --log-level-console $value"
                        ]);
            }
            $level = Log::ERROR;
        } elseif ($this->hasValue('log-level-console')) {
            $level =
                Log::translateLevel
                    ($this->getValue('log-level-console')
                );
        } else {
            $level = self::LOG_CONSOLE_LEVEL;
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
        $file = $this->getValue('log');
        if ($file !== null) {
            $path = File::isAbsolute($file) ?
                $file :
                Config::projectRoot() . '/' . $file;
            $file =
                new \CodeRage\Log\Provider\PrivateFile([
                        'path' => $file,
                        'format' => self::LOG_FILE_FORMAT
                    ]);
            $level = $this->getValue('log-level') !== null ?
                Log::translateLevel($this->getValue('log-level')) :
                self::LOG_FILE_LEVEL;
            $log->registerProvider($file, $level);
        }

        return new Engine(['log' => $log]);
    }

    /**
     * Helper for the constructor
     *
     * @param \CodeRage\Util\CommandLine $cmd
     */
    private static function addConfigOptions(\CodeRage\Util\CommandLine $cmd) : void
    {
        $cmd->addOption([
            'shortForm' => 's',
            'longForm' => 'set',
            'description' =>
                'assigns the configuration variable NAME the value VALUE',
            'placeholder' => 'NAME=VALUE',
            'multiple' => 1
        ]);
        $cmd->addOption([
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
    private static function setProperties(\CodeRage\Util\CommandLine $cmd) : array
    {
        $properties = []; new \CodeRage\Build\Config\Basic;
        $pattern = '/^([_a-z][_a-z0-9]*(?:\.[_a-z][_a-z0-9]*)*)=(.*)$/i';
        if ($values = $cmd->getValue('s', true)) {
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
    private static function unsetProperties(\CodeRage\Util\CommandLine $cmd) : array
    {
        $properties = [];
        if ($values = $cmd->getValue('u', true)) {
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
}
