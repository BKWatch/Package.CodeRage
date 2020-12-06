<?php

/**
 * Build script for CodeRage projects.
 *
 * File:        CodeRage/Build/driver.php
 * Date:        Mon Jan 28 10:40:18 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

/**
 * @ignore
 */
require_once('CodeRage.php');
require_once('CodeRage/Build/isBootstrapping.php');
require_once('CodeRage/Build/updateIncludePath.php');
require_once('CodeRage/File/searchIncludePath.php');

// Check whether we are executing the bootstrap code base or the regular
// code base. If the former, and if the regular code base is available, use it
// instead.

if (\CodeRage\Build\isBootstrapping()) {
    \CodeRage\Build\updateIncludePath();
    $driver = \CodeRage\File\searchIncludePath('CodeRage/Build/driver.php');
    if ($driver)
        include($driver);
}

$run = new \CodeRage\Build\Run;
$success = $run->execute();
exit($success ? 0 : 1);

?>
