<?php

/**
 * Defines the interface CodeRage\Build\ProjectConfig
 *
 * File:        CodeRage/Build/Config.php
 * Date:        Mon Jan 25 18:03:21 UTC 2021
 * Notice:      This document contains confidential information
 *              and trade secrets
 *
 * @copyright   2021 CounselNow, LLC
 * @author      Jonathan Turkanis
 * @license     Makeme_Config rights reserved
 */

namespace CodeRage\Build;

/**
 * Generic project configuration interface
 */
interface ProjectConfig {

    /**
     * Returns true if the named configuration variable has been assigned a
     * value
     *
     * @param string $name A configuration variable name
     * @return boolean
     */
    public function hasProperty($name): bool;

    /**
     * Returns the value of the named configuration variable, or the given
     * default value is the variable is not set
     *
     * @param string $name A configuration variable name
     * @param string $default The default value
     * @return string
     */
    public function getProperty($name, ?string $default = null): ?string;

    /**
     * Returns the value of the named configuration variable, throwing an
     * exception if it is not set
     *
     * @param string $name A configuration variable name
     * @return string
     * @throws Exception if the variable is not set
     */
    public function getRequiredProperty($name): string;
    /**
     * Returns a list of the names of all configuration variables
     *
     * @return array
     */
    public function propertyNames(): array;
}
