<?php

/**
 * Defines the function CodeRage\Build\Svn\checkout.
 *
 * File:        CodeRage/Build/Svn/checkout.php
 * Date:        Mon Jan 12 13:58:57 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Svn;

use function CodeRage\Util\escapeShellArg;

/**
 * @ignore
 */
require_once('CodeRage/Util/escapeShellArg.php');
require_once('CodeRage/Util/system.php');

/**
 * Runs svn checkout.
 *
 * @param CodeRage\Build\Run $run The current run of the build system.
 * the repository username and password.
 * @param string $url The Subversion URL
 * @param string $path The target directory.
 * @param string $revision The revision, if any.
 */
function checkout(\CodeRage\Build\Run $run, $url, $path, $revision = null,
    $export = false)
{
    $command = $export ? 'export' : 'checkout';
    $config = $run->buildConfig();
    $url = escapeShellArg($url);
    $path = escapeShellArg($path);
    $revision = $revision ?
        ' -r ' . escapeShellArg($revision) :
        '';
    $username = ($arg = $config->repositoryUsername()) ?
        ' --username ' . escapeShellArg($arg) :
        '';
    $password = ($arg = $config->repositoryPassword()) ?
        ' --password ' . escapeShellArg($arg) :
        '';
    $command =
        "svn $command --non-interactive$revision$username$password $url $path";
    if ($str = $run->getStream(\CodeRage\Log::DEBUG))
        $str->write("Executing command: $command");
    \CodeRage\Util\system($command);
}
