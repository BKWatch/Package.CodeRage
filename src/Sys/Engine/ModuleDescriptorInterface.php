<?php

/**
 * Defines the interface CodeRage\Sys\Engine\ModuleDescriptorInterface
 *
 * File:        CodeRage/Sys/Engine/ModuleDescriptorInterface.php
 * Date:        Thu Nov 12 23:10:07 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

/**
 * Stores information about a module
 */
interface ModuleDescriptorInterface
{
    /**
     * Returns the module class name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns information about the module, e.g., title, description, and
     * version. The returned instance need not be of the same class as the
     * module being described.
     *
     * @return CodeRage\Sys\Module
     */
    public function getMetadata(): \CodeRage\Sys\ModuleInterface;

    /**
     * Returns the list of datasource bindings for the module
     *
     * @return array A list of instances of CodeRage\Sys\DataSource\Bindings
     */
    public function getBindings(): array;

    /**
     * Returns an integer used to define an ordering on the collection of
     * configured modules in which each module is strictly greater than all the
     * modules on which it depends
     *
     * @return int
     */
    public function getRank(): int;
}
