<?php

/**
 * Defines the class CodeRage\Sys\CommandLine
 *
 * File:        CodeRage/Sys/CommandLine.php
 * Date:        Thu Jan 24 13:39:40 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use CodeRage\Error;
use CodeRage\File;
use CodeRage\Log;
use CodeRage\Text;

/**
 * Implements the "crush" command
 */
class CommandLine extends \CodeRage\Util\CommandLine {

    /**
     * Constructs an instance of CodeRage\Sys\CommandLine
     */
    public function __construct()
    {
        parent::__construct([
            'name' => 'crush',
            'description' => 'The CodeRage command-line',
            'synopsis' => 'crush [options] [command [options]]',
            'noEngine' => true
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
        $this->addSubcommand(new Command\Config);
        $this->addSubcommand(new Command\Build);
        $this->addSubcommand(new Command\Install);
        $this->addSubcommand(new Command\Sync);
        $this->addSubcommand(new Command\Clean);
        $this->addSubcommand(new Command\Reset);
        $this->addSubcommand(new Command\Info);
    }
}
