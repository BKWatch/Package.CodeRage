<?php

/**
 * Defines the class CodeRage\Build\CommandLine
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
 * Implements the "crush" command
 */
final class CommandLine extends \CodeRage\Util\CommandLine {

    /**
     * Constructs an instance of CodeRage\Build\CommandLine
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
        $this->addSubcommand(new Command\Config);
        $this->addSubcommand(new Command\Build);
        $this->addSubcommand(new Command\Install);
        $this->addSubcommand(new Command\Sync);
        $this->addSubcommand(new Command\Clean);
        $this->addSubcommand(new Command\Reset);
        $this->addSubcommand(new Command\Info);
    }
}
