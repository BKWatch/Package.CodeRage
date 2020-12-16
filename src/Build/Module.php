<?php

/**
 * Defines the class CodeRage\Build\Module
 *
 * File:        CodeRage/Build/Module.php
 * Date:        Wed Dec 16 16:08:34 UTC 2020
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2020 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     All rights reserved
 */

namespace CodeRage\Build;

/**
 * Module interface
 */
interface Module {

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
     * Returns the list of class names of the modules on which this module
     * depends
     *
     * @return array
     */
    public function dependencies(): array;

    /**
     * Returns the path to a database table definitions file
     *
     * @return string
     */
    public function tables(): ?string;

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
     * Events the "build" event
     *
     * @return string
     */
    public function build(Engine $engine): string;

    /**
     * Events the "install" event
     *
     * @return string
     */
    public function install(Engine $engine): string;

    /**
     * Events the "sync" event
     *
     * @return string
     */
    public function sync(Engine $engine): string;
}
