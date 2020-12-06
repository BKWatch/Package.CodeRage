<?php

/**
 * Defines the interface CodeRage\Sys\Engine\DependencyAnalyzerInterface
 *
 * File:        CodeRage/Sys/Engine/DependencyAnalyzerInterface.php
 * Date:        Fri Nov 13 17:57:21 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

/**
 * Component that takes a project configuration as input and produces a module
 * store
 */
interface DependencyAnalyzerInterface
{
    /**
     * Generates a module store from the given project configuration
     *
     * @param CodeRage\Sys\ConfigInterface $config
     * @return CodeRage\Sys\Engine\ModuleStoreInterface
     */
    public function analyze(ConfigInterface $config): ModuleStoreInterface;
}
