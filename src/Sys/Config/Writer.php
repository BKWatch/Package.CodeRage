<?php

/**
 * Defines the interface CodeRage\Sys\Config\Writer.
 *
 * File:        CodeRage/Sys/Config/Writer.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Config;

/**
 * Interface implemented by components that serialize configurations.
 */
interface Writer {

    /**
     * Writes the given configuration to the specified file
     *
     * @param CodeRage\Sys\ProjectConfig $properties
     * @param string $path
     * @throws Exception
     */
    public function write(\CodeRage\Sys\ProjectConfig $properties, string $path): void;
}
