<?php

/**
 * Defines the class CodeRage\Sys\Engine\DefaultBuildStore
 *
 * File:        CodeRage/Sys/Engine/DefaultBuildStore.php
 * Date:        Thu Nov 19 00:43:23 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use Traversable;
use CodeRage\File;
use CodeRage\Sys\Util;

/**
 * Implementation of CodeRage\Sys\BuildStoreInterface that stores data in the
 * ".build" subdirectory of the project root directory
 */
class DefaultBuildStore extends BasicBuildStore
{
    /**
     * Constructs an instance of CodeRage\Sys\Engine\DefaultBuildStore
     *
     * @param string $root The root directory
     */
    public function __construct(string $root)
    {
        $root = Util::getProjectRoot();
        parent::__construct(File::join($root, '.build'));
    }
}
