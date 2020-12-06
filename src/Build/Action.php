<?php

/**
 * Defines the interface CodeRage\Build\Action and the function
 * CodeRage\Build\loadAction.
 *
 * File:        CodeRage/Build/Action.php
 * Date:        Fri Jan 02 10:11:06 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * @ignore
 */
require_once('CodeRage/File/checkReadable.php');
require_once('CodeRage/File/searchIncludePath.php');

/**
 * Represents a top-level category of task that can be performed by
 * makeme.php.
 *
 */
interface Action {

    /**
     * The name of this action; must be the same as the long form of the
     * command-line option used to invoke this action, minus the dashes.
     *
     * @return string
     */
    function name();

    /**
     * Checks the command line of the given run for consistency with this
     * action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function checkCommandLine(Run $run);

    /**
     * Returns true if this build action requires that a project configuration
     * be constructed.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @return boolean
     */
    function requiresProjectConfig(Run $run);

    /**
     * Executes this action.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     */
    function execute(Run $run);
}
