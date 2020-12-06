<?php

/**
 * defines the function CodeRage\Build\addToPath.
 *
 * File:        CodeRage/Build/addToPath.php
 * Date:        Sat Apr 18 16:21:45 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use Throwable;
use CodeRage\Log;
use function CodeRage\Util\escapeShellArg;
use function CodeRage\Util\system;

/**
 * @ignore
 */
require_once("CodeRage/File/rm.php");
require_once("CodeRage/Log.php");
require_once('CodeRage/File/findExecutable.php');
require_once('CodeRage/File/isAbsolute.php');
require_once("CodeRage/Util/os.php");
require_once('CodeRage/Util/escapeExecutable.php');
require_once('CodeRage/Util/escapeShellArg.php');
require_once('CodeRage/Util/system.php');

/**
 * Makes the specified executables accessible thorugh the PATH environment
 * variable, by updating it (Windows) or creating symbolic links (UNIX).
 *
 * @param mixed $paths An absolute file pathname or list of absolute file
 * pathnames.
 */
function addToPath($paths)
{
    $log = new Log('build');
    if (is_string($paths))
        $paths = [$paths];
    foreach ($paths as $p) {
        if (!\CodeRage\File\isAbsolute($p))
            $log->logError("Failed adding '$f': path is not absolute");
        if (\CodeRage\Util\os() == 'windows') {

            // Check whether $p is already in PATH
            $dir = realpath(dirname($p));
            $path = getenv('PATH');
            $path = chop($path, ';\\');
            $found = false;
            foreach (explode(';', $path) as $d)
                if (realpath($d) == $dir) {
                    $found = true;
                    break;
                }
            if ($found)
                continue;

            if ($str = $log->getStream(Log::INFO))
                $str->write("Updating PATH environment variable");

            // Check for utility setx.exe
            $exes = \CodeRage\File\findExecutable('setx');
            if (sizeof($exes) == 0)
                if ($str = $log->getStream(Log::ERROR)) {
                    $message =
                        "Failed updating the system PATH environment to " .
                        "include '$dir': setx.exe not found. Please update " .
                         "PATH manually";
                    $str->write($message);
                    continue;
                }

            // Update path
            $setx = \CodeRage\Util\escapeExecutable($exes[0]);
            $newPath = "$dir;$path";
            $command =
                "$setx PATH " . escapeShellArg($newPath) . " -m";
            try {
                system($command);
                putenv("PATH=$newPath");
            } catch (Throwable $e) {
                if ($str = $log->getStream(Log::ERROR)) {
                    $message =
                        "Failed updating the system PATH environment to " .
                        "include '$dir': " . $e->getMessage() . ". You may " .
                        "to run makeme with administrative privileges.";
                    $str->write($message);
                }
            }
        } else {
            $target = '/usr/bin/' . basename($p);
            \CodeRage\File\rm($target);
            $command =
                "ln -s " . escapeShellArg(realpath($p)) . " " .
                escapeShellArg($target);
            try {
                system($command);
            } catch (Throwable $e) {
                if ($str = $log->getStream(Log::ERROR)) {
                    $message =
                        "Failed creating symbolic link: " . $e->getMessage();
                    $str->write($message);
                }
            }
        }
    }
}
