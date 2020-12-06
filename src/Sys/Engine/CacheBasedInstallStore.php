<?php

/**
 * Defines the interface CodeRage\Sys\Engine\CacheBasedInstallStore
 *
 * File:        CodeRage/Sys/Engine/CacheInstallStore.php
 * Date:        Thu Nov 19 05:54:34 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys\Engine;

use Psr\SimpleCache\CacheInterface;
use CodeRage\Error;
use CodeRage\Sys\DataSource\Bindings;

/**
 * Implementation of CodeRage\Sys\InstallStoreInterface based on a PSR-16 simple
 * cache
 */
class CacheBasedInstallStore implements InstallStoreInterface
{

    /**
     * Construct an instance of CodeRage\Sys\Engine\CacheBasedInstallStore
     *
     * @param Psr\SimpleCache\CacheInterface $cache
     */
    public function __construct(CacheInterface $cache, string $prefix = '')
    {
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    /**
     * Returns the schema version for the specified module with the given data
     * source bindings, if any
     *
     * @param string $module The module name
     * @param CodeRage\Sys\DataSource\Bindings $bindings The data source
     *   bindings
     * @return int
     */
    public function getSchemeVersion(string $module, ?Bindings $bindings): ?int
    {
        $value = $this->cache->get($this->getKey($module, $binding));
        if ($value !== null && !is_int($value)) {
            throw new Error([
                'status' => 'INTERNAL_ERROR',
                'details' =>
                    "Expected integral schema version for module '$module' " .
                    ($bindings !== null ? "with bindings '$bindings'" : "") .
                    "; found " . Error::formatValue($value)
            ]);
        }
        return $value;
    }

    /**
     * Sets the schema version for the specified module with the given data
     * source bindings
     *
     * @param string $module The module name
     * @param CodeRage\Sys\DataSource\Bindings $bindings The data source
     *   bindings
     */
    public function setSchemeVersion(string $module, ?Bindings $bindings, int $schemaVersion): void
    {
        $this->cache->get($this->getKey($module, $binding), $schemaVersion);
    }

    private function getKey(string $module, ?DataSource\Bindings $bindings)
    {
        return "module-schema-version:$module" . ($bindings ?: ":$bindings");
    }

    /**
     * @var Psr\SimpleCache\CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $prefix;
}
