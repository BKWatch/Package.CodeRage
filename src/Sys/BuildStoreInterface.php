<?php

/**
 * Defines the interface CodeRage\Sys\BuildStoreInterface
 *
 * File:        CodeRage/Sys/BuildStoreInterface.php
 * Date:        Thu Oct 22 21:54:18 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

use Traversable;

/**
 * Stores information generated at build time
 */
interface BuildStoreInterface extends Traversable
{
    /**
     * Creates a file with the specified contents and associates it with the
     * given key
     *
     * @param string $key The key
     * @param string $contents The file contents
     * @return string
     */
    public function createFileEntry(string $key, string $content): string;

    /**
     * Creates a directory and associates it with the given key
     *
     * @param string $key The key
     * @return string
     */
    public function createDirectoryEntry(string $key): string;

    /**
     * Returns the path of the file or directory associated with the given key,
     * if any
     *
     * @param string $key The key
     * @return string
     */
    public function getEntry(string $key): ?string;

    /**
     * Returns the path of the file or directory associated with the given key
     *
     * @param string $key The key
     * @return string
     */
    public function deleteEntry(string $key): void;

    /**
     * Deletes the information in this store
     */
    public function clear(): void;

    /**
     * Copies the information from this store into the given store
     *
     * @param CodeRage\Sys\BuildStoreInterface The target store
     */
    public function copy(BuildStoreInterface $target): void;
}
