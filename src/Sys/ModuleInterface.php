<?php

/**
 * Defines the interface CodeRage\Sys\ModuleInterface
 *
 * File:        CodeRage/Sys/Module.php
 * Date:        Tue Nov 10 18:05:03 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Sys;

/**
 * Module interface
 */
interface ModuleInterface
{
    /**
     * Returns a descriptive label of this module
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Returns a brief description of this module
     *
     * @return array
     */
    public function getDescription(): string;

    /**
     * Returns a string satisfying the requirments of a Semantic Versioning
     * 2.0.0 version number
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Returns the modules on which this module depends, as a list of pairs
     * of the form [M, V], where M is the class name of a module and V is a
     * version constraint specifier
     *
     * @return array
     */
    public function getDependencies(): array;

    /**
     * Returns a list of the modules that this module is designed to replace, as
     * a list of pairs of the form [M, V], where M is the class name of a module
     * and V is a version constraint specifier
     *
     * @return array
     */
    public function getReplaces(): array;

    /**
     * Returns an associative array mapping event class names to event handler
     * specifiers. Each specifier must have one of the following forms:
     *
     *   - [X, Y], Where X is the name of a class and Y is the name of a
     *     method of that class
     *   - X, Where X is a method of this module
     *
     * @return array
     */
    public function getEventHandlers(): array;

    /**
     * Returns a list of data source specifiers, as associative arrays with
     * the following keys:
     *   name - The symbolic name of the data source, as an identifier
     *   type - The type of the data source, e.g., "sql", as an alphanumeric
     *     string
     *   schemaVersion - The version of the database schema used in the current
     *     codebase, as a positive integer
     *
     * @return array
     */
    public function getDataSources(): array;
}
