<?php

/**
 * Defines the class CodeRage\Build\Action\Help.
 *
 * File:        CodeRage/Build/Action/Help.php
 * Date:        Tue Jan 06 16:51:57 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Action;

use CodeRage\Build\Run;
use CodeRage\Error;

/**
 * @ignore
 */

/**
 * Represents the 'help' build action.
 */
class Help implements \CodeRage\Build\Action {

    /**
     * Returns 'help'.
     *
     * @return string
     */
    function name() { return 'help'; }

    /**
     * Checks the command line of the given run for consistency with the 'help'
     * build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function checkCommandLine(Run $run)
    {
        $commandLine = $run->commandLine();
        if (sizeof($commandLine->arguments()))
            throw new
                Error([
                    'message' =>
                        "Targets cannot be specified with the option --help"
                ]);
        foreach ($commandLine->options() as $opt)
            if ($opt->hasExplicitValue() && $opt->longForm() != 'help')
                throw new
                    Error([
                        'message' =>
                            "The option --help cannot be combined with " .
                            "other options"
                    ]);
    }

    /**
     * Returns true.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return boolean
     */
    function requiresProjectConfig(Run $run)
    {
        return false;
    }

    /**
     * Executes the 'help' build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        echo $run->commandLine()->usage();
    }
}
