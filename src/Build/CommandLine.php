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
class CommandLine extends \CodeRage\Util\CommandLine {

    /**
     * Options that can be used with any build actions except help and info.
     *
     * @var string
     */
    const BUILD_ACTIONS = 'build test clean reset info help';

    /**
     * Options that can be used with any build actions except help and info.
     *
     * @var string
     */
    const DEFAULT_ACTION = 'build';

    /**
     * Options that can be used with any build actions except help and info.
     *
     * @var string
     */
    const COMMON_OPTIONS =
        'non-interactive quiet log log-level log-level-console log-level-smtp
         smtp-to smtp-from smtp-host smtp-port smtp-username smtp-password
         smtp-ssl';

    /**
     * Options relating to logging.
     *
     * @var string
     */
    const LOG_OPTIONS = 'log log-level log-level-console log-level-smtp';

    /**
     * Options relating to code repositories.
     *
     * @var string
     */
    const REPO_OPTIONS = 'repo-url repo-branch';

    /**
     * Options that can be used with any build actions except help and info.
     *
     * @var string
     */
    const SMTP_OPTIONS =
        'smtp-to smtp-from smtp-host smtp-port smtp-username smtp-password
         smtp-ssl';

    /**
     * Options that specify login info for the test database
     *
     * @var string
     */
    const DB_OPTIONS = 'db-dbms db-host db-username db-password';

    /**
     * The options that specified where the CodeRage tools should be installed.
     *
     * @var string
     */
    const INSTALL_OPTIONS = 'minimal shared local';

    /**
     * Options related to configuration.
     *
     * @var string
     */
    const CONFIG_OPTIONS = 'config sys-config set unset';

    /**
     * Options related to testing.
     *
     * @var string
     */
    const TEST_OPTIONS = 'project-url project-branch project-path';

    /**
     * The default log level for console logging.
     *
     * @var int
     */
    const LOG_CONSOLE_LEVEL = Log::INFO;

    /**
     * Value of the 'stream' parameter to the CodeRage\Log\Provider\Console
     * constructor
     *
     * @var int
     */
    const LOG_CONSOLE_STREAM = 'stderr';

    /**
     * Value of the 'format' parameter to the CodeRage\Log\Provider\Console
     * constructor
     *
     * @var int
     */
    const LOG_CONSOLE_FORMAT = 0;

    /**
     * The log level for entry counting.
     *
     * @var int
     */
    const LOG_COUNTER_LEVEL = Log::WARNING;

    /**
     * The default log level for file logging.
     *
     * @var int
     */
    const LOG_FILE_LEVEL = Log::INFO;

    /**
     * The default log level for file logging.
     *
     * @var int
     */
    const LOG_FILE_FORMAT = \CodeRage\Log\Entry::ALL_EXCEPT_SESSION;

    /**
     * The log level for SMTP logging.
     *
     * @var int
     */
    const LOG_SMTP_LEVEL = Log::WARNING;

    /**
     * The build action.
     *
     * @var CodeRage\Build\Action.
     */
    private $action;

    /**
     * Returns a parsed command-line object.
     */
    function __construct()
    {
        parent::__construct(
            'makeme [options] [target ...]',
            'Build or install a CodeRage project'
        );
        $this->addOption([
            'longForm' => 'build',
            'description' => 'builds a CodeRage project',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'longForm' => 'test',
            'description' =>
                'checks out and builds a temporary CodeRage project',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'longForm' => 'clean',
            'description' => 'removes files generated by --build',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'longForm' => 'reset',
            'description' =>
                'removes files generated by --build and clears the ' .
                'build history',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'shortForm' => 'i',
            'longForm' => 'info',
            'description' =>
                'displays information about a CodeRage project',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'longForm' => 'minimal',
            'description' =>
                'used with --install to indicate that only files ' .
                'necessary for bootstrapping future installations should ' .
                'be installed (to <<PATH>>)',
            'valueOptional' => true
        ]);
        $this->addOption([
            'longForm' => 'shared',
            'description' =>
                'used with --install to indicate that CodeRage tools ' .
                'should be installed to a shared location (<<PATH>>)',
            'valueOptional' => true
        ]);
        $this->addOption([
            'longForm' => 'local',
            'description' =>
                'used with --install to indicate that CodeRage tools ' .
                'should be instaled to the project directory',
            'type' => 'boolean'
        ]);
        $this->addOption([
            'longForm' => 'repo-url',
            'description' => '<<URL>> of the CodeRage repository',
        ]);
        $this->addOption([
            'longForm' => 'repo-branch',
            'description' => 'Git ref for the CodeRage repository',
        ]);
        $this->addOption([
            'longForm' => 'project-url',
            'description' => '<<URL>> of the test project',
        ]);
        $this->addOption([
            'longForm' => 'project-branch',
            'description' => 'Git ref for the test project',
        ]);
        $this->addOption([
            'longForm' => 'project-path',
            'description' =>
                'path of project build directory within test project',
        ]);
        $this->addOption([
            'longForm' => 'config',
            'description' =>
                'a configuration file to use in addition to the ' .
                'project definition file; values in <<PATH>> take ' .
                'precendence over those in the project configuration file'
        ]);
        $this->addOption([
            'longForm' => 'sys-config',
            'description' => 'path of the system-wide configuration file',
            'placeholder' => 'PATH'
        ]);
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
        $this->addOption([
            'longForm' => 'non-interactive',
            'description' => 'suppresses requests for user input',
            'type' => 'boolean'
        ]);
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
        $this->addOption([
           'longForm' => 'log-level-smtp',
            'description' =>
                'specifies the log level for SMTP logging; ' .
                '<<LEVEL>> can be one of CRITICAL, ERROR, WARNING, INFO, ' .
                'VERBOSE, or DEBUG; defaults to INFO'
        ]);
        $this->addOption([
            'longForm' => 'smtp-to',
            'description' => 'sends warnings and errors to <<EMAIL>>'
        ]);
        $this->addOption([
            'longForm' => 'smtp-from',
            'description' => 'specifies the From header for SMTP messages',
            'placeholder' => 'EMAIL'
        ]);
        $this->addOption([
            'longForm' => 'smtp-host',
            'description' => 'specifies the SMTP server',
            'placeholder' => 'HOST'
        ]);
        $this->addOption([
            'longForm' => 'smtp-port',
            'description' => 'specifies the SMTP port',
            'placeholder' => 'PORT'
        ]);
        $this->addOption([
            'longForm' => 'smtp-username',
            'description' => 'specifies the SMTP username',
            'placeholder' => 'USER'
        ]);
        $this->addOption([
            'longForm' => 'smtp-password',
            'description' => 'specifies the SMTP password',
            'placeholder' => 'PSWD'
        ]);
        $this->addOption([
            'longForm' => 'smtp-ssl',
            'description' => 'specifies that SSL should be used',
            'placeholder' => 'yes|no'
        ]);
        $this->addOption([
            'longForm' => 'db-dbms',
            'description' => 'specifies the DBMS of the test database',
            'placeholder' => 'DBMS'
        ]);
        $this->addOption([
            'longForm' => 'db-host',
            'description' => 'specifies test database server',
            'placeholder' => 'HOST'
        ]);
        $this->addOption([
            'longForm' => 'db-username',
            'description' => 'specifies test database username',
            'placeholder' => 'USER'
        ]);
        $this->addOption([
            'longForm' => 'db-password',
            'description' => 'specifies test database password',
            'placeholder' => 'PASS'
        ]);
    }

    /**
     * Returns the underlying build action.
     *
     * @return CodeRage\Build\Action
     * @throws CodeRage\Error if check() has not been called.
     */
    function action()
    {
        if ($this->action === null)
            throw new Error(['message' => "No build action has been set"]);
        return $this->action;
    }

    /**
     * Sets the underlying build action.
     *
     * @throws CodeRage\Error if this command line is inconsistent.
     */
    function setAction()
    {
        $action = null;
        $options = Text::split(self::BUILD_ACTIONS);
        foreach ($options as $opt) {
            if ($this->hasOption($opt) && $this->getValue($opt)) {
                if ($action !== null) {
                    throw new
                        Error(['message' =>
                            'At most one of the options ' .
                            $this->printOptions($options) . ' may be specified'
                        ]);
                }
                $action = $opt;
                break;
            }
        }
        if (!$action)
            $action = 'build';
        $class = '\CodeRage\Build\Action\\' . ucfirst($action);
        $this->action = new $class;
    }

    /**
     * Constructs and returns an instance of CodeRage\Log based on this command line,
     * using the specified project root
     *
     * @param string $projectRoot the project root directory
     * @return CodeRage\Log
     */
    function createLog($projectRoot)
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
                "$projectRoot/$file";
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

        // Configure provider 'smtp'
        $options = Text::split(CommandLine::SMTP_OPTIONS);
        if ($this->getValue('smtp-to') !== null) {
            $values = [];
            foreach ($options as $opt) {
                $value = $this->getValue($opt);
                if ($value !== null)
                    $values[substr($opt, 5)] = $value;
            }
            $smtp = new \CodeRage\Log\Provider\Smtp($values);
            $level = $this->hasValue('log-level-smtp') ?
                Log::translateLevel($this->getValue('log-level-smtp')) :
                self::LOG_SMTP_LEVEL;
            $log->registerProvider($smtp, $level);
        } else {
            foreach ($options as $opt)
                if ($this->getValue($opt) !== null)
                    throw new
                        Error(['message' =>
                            "Option --$opt requires option --smtp-to"
                        ]);
        }

        return $log;
    }

    /**
     * Throws an exception if the given array of options is invalid
     *
     * @param array $values An array mapping option long forms to proposed
     *   values
     * @throws CodeRage\Error
     */
    protected function doPostParse()
    {
        $values = $this->values();
        if (isset($values['smtp-username']) && !isset($values['smtp-password']))
            throw new Error(['message' => 'Missing option --smtp-password']);
        if (isset($values['smtp-to']) && !isset($values['smtp-from']))
            $this->lookupOption('smtp-from')
                ->setValue('no-reply@localhost.localdomain');
    }

    /**
     * Returns a human readable string consisting of a list of the given
     * options.
     *
     * @param array $options A list of option long forms
     */
    static function printOptions($options)
    {
        switch (sizeof($options)) {
        case 0:
            throw new Error(['message' => "No options specified"]);
        case 1:
            return "--{$options[0]}";
        case 2:
            return "--{$options[0]} and --{$options[1]}";
        default:
            $result = '';
            for ($z = 0, $n = sizeof($options); $z < $n; ++$z) {
                $result .= "--{$options[$z]}";
                if ($z < $n - 2)
                    $result .= ', ';
                if ($z == $n - 2)
                    $result .= 'and ';
            }
            return $result;
        }
    }
}
