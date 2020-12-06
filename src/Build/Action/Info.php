<?php

/**
 * Defines the class CodeRage\Build\Action\Info.
 *
 * File:        CodeRage/Build/Action/Info.php
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
 * Represents the 'info' build action.
 */
class Info implements \CodeRage\Build\Action {

    /**
     * Returns 'info'.
     *
     * @return string
     */
    function name() { return 'info'; }

    /**
     * Checks the command line of the given run for consistency with the 'info'
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
                        "Targets cannot be specified with the option --info"
                ]);
        $common = \CodeRage\Text\split(\CodeRage\Build\CommandLine::COMMON_OPTIONS);
        foreach ($commandLine->options() as $opt) {
            if ($opt->hasExplicitValue()) {
                $long = $opt->longForm();
                if ($long != 'info' && !in_array($long, $common))
                    throw new
                        Error([
                            'message' =>
                                "The option --$long cannot be combined with " .
                                "the option --info"
                        ]);
            }
        }
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
     * Executes the 'info' build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        echo (string) $run->buildConfig();
    }
}
