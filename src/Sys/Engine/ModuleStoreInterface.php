<?php

/**
 * Defines the interface CodeRage\Sys\Engine\ModuleStoreInterface
 *
 * File:        CodeRage/Sys/Engine/ModuleStore.Interfacephp
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
 * Represents the collection of configured modules
 */
interface ModuleStoreInterface extends Traversable, ArrayAccess
{
    /**
     * Returns information about the module with the give name, if any
     *
     * @return string
     */
    public function getModule(string $name): ?ModuleDescriptorInterface;
}
