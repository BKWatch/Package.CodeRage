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
require_once('CodeRage/Build/updateIncludePath.php');
require_once('CodeRage/File/searchIncludePath.php');

$run = new \CodeRage\Build\Run;
$success = $run->execute();
exit($success ? 0 : 1);
