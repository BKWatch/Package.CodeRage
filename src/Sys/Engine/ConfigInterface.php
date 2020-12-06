<?php

/**
 * Defines the interface CodeRage\Sys\Engine\ConfigInterface
 *
 * File:        CodeRage/Sys/Engine/ConfigInterface.php
 * Date:        Fri Nov 13 18:15:14 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

/**
 * Defines a collection of modules, data sources, and configuration options
 */
interface ConfigInterface
{
    /**
     * Returns a list of module configurations
     *
     * @return array A list of instances of CodeRage\Sys\Module\ConfigInterface
     */
    public function getModules(): array;

    /**
     * Returns a list of data source configurations
     *
     * @return array A list of instances of
     *   CodeRage\Sys\DataSource\ConfigInterface
     */
    public function getDataSources(): array;

    /**
     * Returns a list collection of configuration options
     *
     * @return CodeRage\Sys\ConfigInterface
     */
    public function getOptions(): Config;
}
