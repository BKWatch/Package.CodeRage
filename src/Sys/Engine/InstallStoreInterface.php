<?php

/**
 * Defines the interface CodeRage\Sys\Engine\InstallStoreInterface
 *
 * File:        CodeRage/Sys/Engine/InstallStoreInterface.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use CodeRage\Sys\DataSource\Bindings;

/**
 * Interface for components that store information about installed modules
 */
interface InstallStoreInterface
{
    /**
     * Returns the schema version for the specified module with the given data
     * source bindings
     *
     * @param string $module The module name
     * @param CodeRage\Sys\DataSource\Bindings $bindings The data source
     *   bindings
     * @return int
     */
    public function getSchemeVersion(string $module, ?Bindings $bindings): int;

    /**
     * Sets the schema version for the specified module with the given data
     * source bindings
     *
     * @param string $module The module name
     * @param CodeRage\Sys\DataSource\Bindings $bindings The data source
     *   bindings
     * @param int $schemaVersion
     */
    public function setSchemeVersion(string $module, ?Bindings $bindings, int $schemaVersion): void;
}
