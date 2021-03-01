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
 * Provided for backward compatibility
 */
final class CommandLine extends \CodeRage\Sys\CommandLine {

    /**
     * Constructs an instance of CodeRage\Sys\CommandLine
     */
    public function __construct()
    {
        parent::__construct();
    }
}
