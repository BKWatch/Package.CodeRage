<?php

/**
 * Defines the class CodeRage\Build\Action\Update.
 *
 * File:        CodeRage/Build/Action/Update.php
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

/**
 * @ignore
 */

/**
 * Represents the 'update' build action.
 */
class Update implements \CodeRage\Build\Action {

    /**
     * Returns 'update'.
     *
     * @return string
     */
    function name() { return 'update'; }

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
     * Checks the command line of the given run for consistency the 'update'
     * build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function checkCommandLine(Run $run)
    {

    }

    /**
     * Executes the 'update' build action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run)
    {

    }
}
