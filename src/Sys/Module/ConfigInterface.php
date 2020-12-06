<?php

/**
 * Defines the interface CodeRage\Sys\Module\ConfigInterface
 *
 * File:        CodeRage/Sys/Module/ConfigInterface.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Module;

/**
 * Module configuration interface
 */
interface ConfigInterface extends Config
{
    /**
     * Returns the name of the module being configured
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the collection of data source bindings
     *
     * @return CodeRage\Sys\DataSourceBindings
     */
    public function getDataSources(): array;
}
