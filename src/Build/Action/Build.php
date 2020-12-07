<?php

/**
 * Defines the class CodeRage\Build\Action\Build.
 *
 * File:        CodeRage/Build/Action/Build.php
 * Date:        Tue Jan 06 16:51:57 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Action;

use CodeRage\Build\CommandLine;
use CodeRage\Build\Run;
use CodeRage\Error;

/**
 * Represents the 'build' build action.
 */
class Build implements \CodeRage\Build\Action {

    /**
     * Returns 'build'.
     *
     * @return string
     */
    function name() { return 'build'; }

    /**
     * Checks the command line of the given run for consistency with the 'build'
     * build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function checkCommandLine(Run $run)
    {
        $commandLine = $run->commandLine();
        $common = split(CommandLine::COMMON_OPTIONS);
        $config = split(CommandLine::CONFIG_OPTIONS);
        $options = array_merge($common, $config);
        foreach ($commandLine->options() as $opt) {
            if ($opt->hasExplicitValue()) {
                $long = $opt->longForm();
                if ($long != 'build' && !in_array($long, $options))
                    throw new
                        Error(['message' =>
                            "The option --$long cannot be combined with " .
                            "the option --build"
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
          return true;
    }

    /**
     * Calls $run->build().
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {
        $run->build();
    }
}
