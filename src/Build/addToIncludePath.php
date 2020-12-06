<?php

/**
 * defines the function CodeRage\Build\addToIncludePath.
 *
 * File:        CodeRage/Build/addToIncludePath.php
 * Date:        Sat Apr 18 16:21:45 MDT 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\Log;

/**
 * @ignore
 */
require_once("CodeRage/Build/Packages/Php/Manager.php");
require_once("CodeRage/File/canonicalize.php");
require_once("CodeRage/File/isAbsolute.php");
require_once("CodeRage/Log.php");
require_once("CodeRage/Util/ErrorHandler.php");

/**
 * Updates the value of include_path in php.ini to include the given
 * path.
 *
 * @param string $dir
 */
function addToIncludePath($dir)
{
    $log = new Log('build');

    // Check whether $dir is already in include_path
    $dir = \CodeRage\File\canonicalize($dir);
    $path = ini_get('include_path');
    foreach (explode(PATH_SEPARATOR, $path) as $d)
        if (realpath($d) == $dir)
            return;

    if ($str = $log->getStream(Log::INFO))
        $str->write("Updating PHP include_path");

    // Construct CodeRage\Build\Packages\Configuration corresponding to
    // current PHP installation
    $manager = new Packages\Php\Manager();
    $version = phpversion();
    $properties =
        [
            'minVersion' => $version,
            'maxVersion' => $version
        ];
    if ($iniPath = get_cfg_var('cfg_file_path'))
        $properties['iniPath'] = $iniPath;
    $configurations = $manager->lookupConfigurations($properties);
    if (sizeof($configurations)) {
          $configurations[0]->addToLibrarySearchPath($dir);
    } else {
        if ($str = $log->getStream(Log::ERROR)) {
            $handler = new \CodeRage\Util\ErrorHandler;
            $str->write(
                $handler->formatError(
                    "Failed updating PHP include_path to include the " .
                    "directory '$dir': Can't locate current PHP " .
                    "installation. Please update php.ini manually."
                )
            );
        }
        return;
    }
}
