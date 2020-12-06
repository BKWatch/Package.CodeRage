<?php

/**
 * Defines the class CodeRage\Build\Resource_.
 *
 * File:        CodeRage/Build/Resource_.php
 * Date:        Sun Jan 04 19:13:37 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

use CodeRage\Error;

/**
 * @ignore
 */
require_once('CodeRage/File/checkReadable.php');

/**
 * Defines two static methods used to access non-PHP files used by the
 * CodeRage.Build.
 */
class Resource_ {

    /**
     * Returns the list of names of regular files in the directory
     * CodeRage/Build/Resource
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param string $file The name of a file, excluding the directory.
     * @return string
     */
    static function listFiles(Run $run)
    {
        $dir = $run->buildConfig()->toolsPath();
        $files = [];
        $hnd = @opendir($dir);
        if ($hnd === false)
            throw new Error(['message' => "Failed reading directory '$dir'"]);
        while (($file = @readdir($hnd)) !== false)
            if (is_file("$dir/$file"))
                $files[] = $file;
        @closedir($hnd);
        return $files;
    }

    /**
     * Returns the contents of the specified file in the directory
     * CodeRage/Build/Resource.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param string $file The name of a file, excluding the directory.
     * @return string
     */
    static function load(Run $run, $file)
    {
        $path =
            $run->buildConfig()->toolsPath() .
            "/CodeRage/Build/Resource/$file";
        \CodeRage\File\checkReadable($path);
        $content = @file_get_contents($path);
        if ($content === false)
            throw new Error(['message' => "Failed loding resource: $file"]);
        return $content;
    }

    /**
     * Returns the path of a file containing the contents of the specified file
     * in the directory CodeRage/Build/Resource.
     *
     * @param CodeRage\Build\Run $run The current run of the build system.
     * @param string $file The name of a file, excluding the directory.
     * @return string
     */
    static function loadFile(Run $run, $file)
    {
        $path =
            $run->buildConfig()->toolsPath() .
            "/CodeRage/Build/Resource/$file";
        if (!file_exists($path))
            throw new Error(['message' => "No such resource: $path"]);
        return $path;
    }
}
