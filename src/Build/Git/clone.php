<?php

/**
 * Defines the function CodeRage\Build\Git\clone.
 *
 * File:        CodeRage/Build/git/clone.php
 * Date:        Tue Jun 20 18:56:41 UTC 2017
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2017 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\git;

use CodeRage\Error;
use function CodeRage\Util\escapeShellArg;
use function CodeRage\Util\system;

/**
 * @ignore
 */
require_once('CodeRage/File/isAbsolute.php');
require_once('CodeRage/File/rm.php');
require_once('CodeRage/Util/escapeShellArg.php');
require_once('CodeRage/Util/system.php');

/**
 * Runs 'git clone'
 *
 * @param CodeRage\Build\Run $run The current run of the build system.
 * the repository username and password.
 * @param string $url The Git repository URL
 * @param string $path The target directory.
 * @param string $branch The Git ref to checkout, if any
 */
function clone_(\CodeRage\Build\Run $run, $url, $path, $branch = null,
    $export = false)
{
    if (!\CodeRage\File\isAbsolute($path))
        throw new
            Error([
                'status' => 'INVALID_PARAMETER',
                'message' => "Expected absolute path; found $path"
            ]);
    $escUrl = escapeShellArg($url);
    $escPath = escapeShellArg($path);
    \CodeRage\Util\system("git clone $escUrl $escPath");
    if ($branch !== null) {
        $cwd = getcwd();
        try {
            chdir($path);
            \CodeRage\Util\system('git checkout ' . escapeShellArg($branch));
        } catch (\Throwable $e) {
            \CodeRage\File\rm($path);
        } finally {
            chdir($cwd);
        }
    }
}
