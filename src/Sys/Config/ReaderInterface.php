<?php

/**
 * Defines the interface CodeRage\Sys\Config\ReaderInterface
 *
 * File:        CodeRage/Sys/Config/ReaderInterface.php
 * Date:        Fri Nov 20 19:54:31 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

use CodeRage\Sys\ConfigInterface;

/**
 * Parses a configuration file
 */
interface ReaderInterface
{
    /**
     * Parses the specified file and returns a configuration object
     *
     * @param string $path The file pathname
     * @return CodeRage\Sys\ConfigInterface
     */
    public function read(string $path): ConfigInterface;
}
