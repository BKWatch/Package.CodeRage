<?php

/**
 * defines the function CodeRage\Build\updateIncludePath.
 *
 * File:        CodeRage/Build/updateIncludePath.php
 * Date:        Tue Nov  3 10:31:22 MST 2009
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Updates the runtime include path to include the closest ancestor
 * directory that contains a configuration file.
 */
function updateIncludePath()
{
    // Determine root directory
    $oldcwd = getcwd();
    for ( $cur = null, $next = $oldcwd;
          $cur != $next &&
              !is_file('./project.xml') &&
              !is_file('./project.ini') &&
              !is_file('./project-root') &&
              !is_file('./website-root');
          $cur = $next, $next = getcwd() )
    {
        chdir('..');
    }
    if ($cur != $next) {

        // Add root directory to include path
        $root = str_replace('\\', '/', $next);
        $path = ini_get('include_path');
        $sep = PATH_SEPARATOR;
        ini_set('include_path', "$root$sep$path");
    }
    chdir($oldcwd);
}
