<?php

/**
 * Defines the class CodeRage\Sys\Module
 *
 * File:        CodeRage/Sys/Module.php
 * Date:        Wed Dec 16 16:08:34 UTC 2020
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
interface Module {

    /**
     * Returns the fully-qualified class name, in dot-separated identifier
     * format
     *
     * @return string
     */
    public function name(): string;

    /**
     * Returns the title
     *
     * @return string
     */
    public function title(): string;

    /**
     * Returns the title
     *
     * @return string
     */
    public function description(): string;

    /**
     * Returns the module configuration file
     *
     * @return string
     */
    public function configFile(): ?string;

    /**
     * Returns the module configuration, as a string-valued associative array
     *
     * @return array
     */
    public function config(): ?array;

    /**
     * Returns the list of class names of the modules on which this module
     * depends
     *
     * @return array
     */
    public function dependencies(): array;

    /**
     * Returns a list of table definition files
     *
     * @return array
     */
    public function tables(): array;

    /**
     * Returns the path to an error code definitions file
     *
     * @return string
     */
    public function statusCodes(): ?string;

    /**
     * Returns an associative array mapping absolute directory paths of
     * files that should be copied into the web server root to the correspinding
     * target directories relative to the web server root
     *
     * @return array
     */
    public function webRoots(): array;

    /**
     * Executes the "build" event
     *
     * @return string
     */
    public function build(Engine $engine): void;

    /**
     * Executes the "install" event
     *
     * @return string
     */
    public function install(Engine $engine): void;

    /**
     * Executes the "sync" event
     *
     * @return string
     */
    public function sync(Engine $engine): void;

    /**
     * Executes the "clean" event
     *
     * @return string
     */
    public function clean(Engine $engine): void;
}
