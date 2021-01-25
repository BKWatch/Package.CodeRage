<?php

/**
 * Defines the interface CodeRage\Build\Config\Writer.
 *
 * File:        CodeRage/Build/Config/Writer.php
 * Date:        Thu Jan 24 10:53:37 MST 2008
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2015 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build\Config;

/**
 * Interface implemented by components that serialize configurations.
 */
interface Writer {

    /**
     * Writes the given property bundle to the specified file.
     *
     * @param CodeRage\Build\ExtendedConfig $properties
     * @param string $path
     * @throws Exception
     */
    function write(\CodeRage\Build\ExtendedConfig $properties, $path);
}
