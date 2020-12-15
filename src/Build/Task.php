<?php

/**
 * Defines the interface CodeRage\Build\Task.
 *
 * File:        CodeRage/Build/Task.php
 * Date:        Mon Feb 25 15:33:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Performs a build action.
 */
interface Task {

    /**
     * Executes this task.
     *
     * @param CodeRage\Build\Engine $engine The build engine
     */
    function execute(Engine $engine);
}
