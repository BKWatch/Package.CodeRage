<?php

/**
 * Clones out the CodeRage repository, builds makeme.php using the script
 * makemakeme.php, and copies the result to the directory specified as the
 * command-line argument.
 *
 * Unlike makemakeme.php, which does not assume that the CodeRage tools are
 * installed, this script requires the CodeRage tools.
 *
 * File:        CodeRage/Build/publishmakeme.php
 * Date:        Sun Jan 16 21:01:39 MST 2011
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

use function CodeRage\File\checkReadable;
use CodeRage\Util\ErrorHandler;
use function CodeRage\Util\escapeShellArg;
use function CodeRage\Util\system;

/**
 * @ignore
 */
require_once('CodeRage/Build/Constants.php');
require_once('CodeRage/File/checkReadable.php');
require_once('CodeRage/File/isAbsolute.php');
require_once('CodeRage/File/tempDir.php');
require_once('CodeRage/Util/escapeShellArg.php');
require_once('CodeRage/Util/system.php');

const REPO_URL = 'git@github.com:BKWatch/CodeRage';
const REPO_BRANCH = 'dev';

try {
    $targetDir = parseCommandLine();
    $toolsDir = checkoutTools();
    $makeme = makeMakeme($toolsDir);
    publish($makeme, $targetDir);
} catch (Throwable $e) {
    $stderr = fopen('php://stderr', 'w');
    fwrite($stderr, "ERROR: " . $e->getMessage() . "\n");
}

/**
 * Returns the directory specified on the command line.
 *
 * @return string
 * @throws Exception if an error occurs
 */
function parseCommandLine()
{
    $argv = isset($GLOBALS['argv']) ?
        $GLOBALS['argv'] :
        ( isset($_SERVER['argv']) ?
              $_SERVER['argv'] :
              null );
    if ($argv === null)
        throw new Exception("Failed parsing command-line: no ARGV provided");
    array_shift($argv);
    if (sizeof($argv) && ($argv[0] == '-q' || $argv[0] == '--quiet')) {
        quiet(true);
        array_shift($argv);
    }
    if (sizeof($argv) != 1)
        throw new
            Exception(
                "Wrong number of command-line arguments: expected 1; found " .
                sizeof($argv)
            );
    $directory = $argv[0];
    if (!\CodeRage\File\isAbsolute($directory))
        throw new
            Exception(
                "Invalid command-line argument: expected absolute path; " .
                "found '$directory'"
            );
    if (!file_exists($directory))
        throw new Exception("No such directory: $directory");
    if (!is_dir($directory))
        throw new Exception("The file '$directory' is not a directory");
    return $directory;
}

/**
 * Checks out the CodeRage trunk and returns the path to the root directory.
 *
 * @return string
 * @throws Exception if an error occurs
 */
function checkoutTools()
{
    if (!quiet())
        echo "Checking out tools ...\n";
    $temp = \CodeRage\File\tempDir();
    $path = "$temp/CodeRage";
    $command =
        'git clone --branch ' . escapeShellArg(REPO_BRANCH) . ' ' .
        escapeShellArg(REPO_URL) . ' ' . escapeShellArg($path);
    system($command);
    return $path;
}

/**
 * Runs the script makemakeme.php and returns the path to the generated script.
 *
 * @param string $toolsDir Root directory of the CodeRage repository
 * @return string
 * @throws Exception if an error occurs
 */
function makeMakeme($toolsDir)
{
    if (!quiet())
        echo "Running makemakeme.php ...\n";

    // Change to build directory
    $buildDir = "$toolsDir/Build";
    if (!chdir($buildDir))
        throw new Exception("Failed changing to directory '$buildDir'");

    // Remove old copy of makeme.php
    $makeme = "$buildDir/makeme.php";
    $handler = new ErrorHandler;
    $handler->_unlink($makeme);
    if ($handler->errno())
        throw new Exception($handler->formatError());

    // Run build script
    $makemakeme = "$buildDir/makemakeme.php";
    checkReadable($makemakeme);
    $command = 'php ' . escapeShellArg($makemakeme);
    system($command);
    checkReadable($makeme);

    return $makeme;
}

/**
 * Copies the generated script to its final location.
 *
 * @param string $makeme The path to the generated script
 * @param string $targetDir The directory specified on the command line
 * @throws Exception if an error occurs
 */
function publish($makeme, $targetDir)
{
    if (!quiet())
        echo "Copying makeme.php to '$targetDir' ...\n";

    $handler = new ErrorHandler;
    $handler->_copy($makeme, "$targetDir/makeme.txt");
    if ($handler->errno())
        throw new
           Exception(
               $handler->formatError("Failed copying '$makeme' to '$targetDir'")
           );
}

/**
 * Returns true if informational output should be suppressed.
 *
 * @param boolean $value the new value, if any, for the 'quiet' flag.
 * @return bool
 */
function quiet($value = null)
{
    static $quiet = false;
    if ($value !== null) {
        $quiet = $value;
    } else {
        return $quiet;
    }
}

?>
